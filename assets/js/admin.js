chrome.runtime.onInstalled.addListener(() => {
  chrome.storage.local.set({
    isExtensionEnabled: true,
    authStatus: 'pending',
    folderSizeCache: {},
    currentUser: null,
    lastCacheCleanup: Date.now()
  });
});

let lastTokenRequest = 0;
const TOKEN_COOLDOWN = 1000;

function getAuthToken(interactive = false, prompt = false) {
  return new Promise((resolve, reject) => {
    const now = Date.now();
    
    if (now - lastTokenRequest < TOKEN_COOLDOWN && !interactive) {
      reject(new Error('Troppe richieste di token'));
      return;
    }
    
    lastTokenRequest = now;
    
    const authOptions = { 
      interactive: interactive,
      scopes: ['https://www.googleapis.com/auth/drive.readonly']
    };
    
    if (prompt && interactive) {
      chrome.identity.getAuthToken({ interactive: false }, (existingToken) => {
        if (existingToken) {
          chrome.identity.removeCachedAuthToken({ token: existingToken }, () => {
            requestTokenWithPrompt();
          });
        } else {
          requestTokenWithPrompt();
        }
      });
    } else {
      chrome.identity.getAuthToken(authOptions, handleTokenResponse);
    }
    
    function requestTokenWithPrompt() {
      chrome.identity.getAuthToken({ 
        interactive: true,
        scopes: ['https://www.googleapis.com/auth/drive.readonly']
      }, handleTokenResponse);
    }
    
    function handleTokenResponse(token) {
      if (chrome.runtime.lastError) {
        console.error('Errore di autenticazione:', chrome.runtime.lastError);
        chrome.storage.local.set({ authStatus: 'unauthenticated', currentUser: null });
        reject(chrome.runtime.lastError);
      } else if (!token) {
        console.error('Token non ricevuto');
        chrome.storage.local.set({ authStatus: 'unauthenticated', currentUser: null });
        reject(new Error('Token non ricevuto'));
      } else {
        getUserInfo(token).then(userInfo => {
          chrome.storage.local.get('currentUser', (data) => {
            if (data.currentUser && data.currentUser !== userInfo.emailAddress) {
              clearFolderSizeCache(data.currentUser);
            }
            
            chrome.storage.local.set({ 
              authStatus: 'authenticated', 
              currentUser: userInfo.emailAddress 
            });
            
            resolve(token);
          });
        }).catch(error => {
          console.error('Errore verifica utente:', error);
          chrome.storage.local.set({ authStatus: 'authenticated' });
          resolve(token);
        });
      }
    }
  });
}

async function revokeTokenAndLogout() {
  return new Promise((resolve) => {
    chrome.identity.getAuthToken({ interactive: false }, async (token) => {
      if (token) {
        try {
          await fetch(`https://oauth2.googleapis.com/revoke?token=${token}`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
          });
        } catch (error) {
          console.error('Errore revoca token:', error);
        }
        
        chrome.identity.removeCachedAuthToken({ token: token }, () => {
        });
      }
      
      chrome.storage.local.get('currentUser', (data) => {
        clearFolderSizeCache(data.currentUser).then(() => {
          chrome.storage.local.set({ 
            authStatus: 'unauthenticated', 
            currentUser: null,
            isExtensionEnabled: true
          });
          resolve({ success: true });
        });
      });
    });
  });
}

async function getUserInfo(token) {
  const response = await fetch('https://www.googleapis.com/drive/v3/about?fields=user', {
    headers: { Authorization: `Bearer ${token}` }
  });
  
  if (!response.ok) {
    throw new Error(`HTTP ${response.status}`);
  }
  
  const data = await response.json();
  return data.user;
}

function saveFolderSizeWithUrl(folderId, cacheData, urlKey, userEmail = null) {
  chrome.storage.local.get(['folderSizeCache', 'currentUser'], (data) => {
    const cache = data.folderSizeCache || {};
    const user = userEmail || data.currentUser || 'default';
    
    if (!cache[user]) cache[user] = {};
    if (!cache[user][urlKey]) cache[user][urlKey] = {};
    
    cache[user][urlKey][folderId] = cacheData;
    
    chrome.storage.local.set({ folderSizeCache: cache });
  });
}

function getFolderSizeWithUrl(folderId, urlKey, userEmail = null) {
  return new Promise((resolve) => {
    chrome.storage.local.get(['folderSizeCache', 'currentUser'], (data) => {
      const cache = data.folderSizeCache || {};
      const user = userEmail || data.currentUser || 'default';
      
      const cacheData = cache[user]?.[urlKey]?.[folderId] || null;
      resolve(cacheData);
    });
  });
}

function getUrlCache(urlKey, userEmail = null) {
  return new Promise((resolve) => {
    chrome.storage.local.get(['folderSizeCache', 'currentUser'], (data) => {
      const cache = data.folderSizeCache || {};
      const user = userEmail || data.currentUser || 'default';
      
      const urlCache = cache[user]?.[urlKey] || {};
      resolve(urlCache);
    });
  });
}

function getCacheStats(urlKey, userEmail = null) {
  return new Promise((resolve) => {
    chrome.storage.local.get(['folderSizeCache', 'currentUser'], (data) => {
      const cache = data.folderSizeCache || {};
      const user = userEmail || data.currentUser || 'default';
      
      const userCache = cache[user] || {};
      const totalItems = Object.values(userCache).reduce((sum, urlData) => 
        sum + Object.keys(urlData).length, 0);
      const urlItems = Object.keys(userCache[urlKey] || {}).length;
      
      resolve({
        totalItems,
        urlItems,
        urlsCount: Object.keys(userCache).length
      });
    });
  });
}

async function cleanupOldCache() {
  return new Promise((resolve) => {
    chrome.storage.local.get(['folderSizeCache', 'lastCacheCleanup'], (data) => {
      const now = Date.now();
      const lastCleanup = data.lastCacheCleanup || 0;
      
      if (now - lastCleanup < 6 * 60 * 60 * 1000) {
        resolve();
        return;
      }
      
      const cache = data.folderSizeCache || {};
      const maxAge = 12 * 60 * 60 * 1000;
      let cleaned = 0;
      
      for (const user in cache) {
        for (const urlKey in cache[user]) {
          for (const folderId in cache[user][urlKey]) {
            const cacheData = cache[user][urlKey][folderId];
            if (cacheData?.timestamp && (now - cacheData.timestamp) > maxAge) {
              delete cache[user][urlKey][folderId];
              cleaned++;
            }
          }
          
          if (Object.keys(cache[user][urlKey]).length === 0) {
            delete cache[user][urlKey];
          }
        }
        
        if (Object.keys(cache[user]).length === 0) {
          delete cache[user];
        }
      }
      
      chrome.storage.local.set({ 
        folderSizeCache: cache,
        lastCacheCleanup: now
      });
      
      if (cleaned > 0) {
        console.log(`[Cache Cleanup] Rimossi ${cleaned} elementi vecchi`);
      }
      
      resolve();
    });
  });
}

function saveFolderSizeToCache(folderId, size, userEmail = null, cacheData = null) {
  chrome.storage.local.get(['folderSizeCache', 'currentUser'], (data) => {
    const cache = data.folderSizeCache || {};
    const user = userEmail || data.currentUser || 'default';
    
    if (!cache[user]) cache[user] = {};
    if (!cache[user]['legacy']) cache[user]['legacy'] = {};
    
    cache[user]['legacy'][folderId] = cacheData || {
      size: size,
      timestamp: Date.now(),
      version: '3.4.0'
    };
    
    chrome.storage.local.set({ folderSizeCache: cache });
  });
}

function getFolderSizeFromCache(folderId, userEmail = null) {
  return new Promise((resolve) => {
    chrome.storage.local.get(['folderSizeCache', 'currentUser'], (data) => {
      const cache = data.folderSizeCache || {};
      const user = userEmail || data.currentUser || 'default';
      
      resolve(cache[user]?.['legacy']?.[folderId] || null);
    });
  });
}

function clearFolderSizeCache(userEmail = null) {
  return new Promise((resolve) => {
    if (userEmail) {
      chrome.storage.local.get('folderSizeCache', (data) => {
        const cache = data.folderSizeCache || {};
        delete cache[userEmail];
        chrome.storage.local.set({ folderSizeCache: cache }, resolve);
      });
    } else {
      chrome.storage.local.set({ folderSizeCache: {} }, resolve);
    }
  });
}

setInterval(() => {
  cleanupOldCache();
}, 60 * 60 * 1000);

chrome.runtime.onMessage.addListener((request, sender, sendResponse) => {
  
  if (request.type === "GET_TOKEN") {
    const forcePrompt = request.forcePrompt || false;
    getAuthToken(true, forcePrompt)
      .then(token => sendResponse({ token }))
      .catch(error => sendResponse({ error: error.message }));
    return true;
  } 
  
  if (request.type === "LOGOUT") {
    chrome.identity.getAuthToken({ interactive: false }, (token) => {
      if (token) {
        chrome.identity.removeCachedAuthToken({ token: token }, () => {
        });
      }
    });
    
    chrome.storage.local.get('currentUser', (data) => {
      clearFolderSizeCache(data.currentUser).then(() => {
        chrome.storage.local.set({ 
          authStatus: 'unauthenticated', 
          currentUser: null,
          isExtensionEnabled: true
        });
        sendResponse({ success: true });
      });
    });
    return true;
  }
  
  if (request.type === "LOGOUT_WITH_REVOKE") {
    revokeTokenAndLogout()
      .then(response => sendResponse(response))
      .catch(error => sendResponse({ success: false, error: error.message }));
    return true;
  }
  
  if (request.type === "TOGGLE_EXTENSION") {
    chrome.storage.local.get('isExtensionEnabled', (data) => {
      const isEnabled = !data.isExtensionEnabled;
      chrome.storage.local.set({ isExtensionEnabled: isEnabled });
      sendResponse({ enabled: isEnabled });
    });
    return true;
  } 
  
  if (request.type === "GET_EXTENSION_STATUS") {
    chrome.storage.local.get(['isExtensionEnabled', 'currentUser'], (data) => {
      sendResponse({ 
        enabled: data.isExtensionEnabled !== false,
        currentUser: data.currentUser
      });
    });
    return true;
  }
  
  if (request.type === "CHECK_SUBSCRIPTION") {
    chrome.storage.local.get('currentUser', async (data) => {
      if (!data.currentUser) {
        sendResponse({ hasActiveSubscription: false });
        return;
      }
      
      try {
        const response = await fetch('https://backend-gdfs.onrender.com/api/check-subscription', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ userId: data.currentUser })
        });
        
        const result = await response.json();
        
        chrome.storage.local.set({ 
          hasActiveSubscription: result.hasActiveSubscription,
          subscriptionData: result.subscription 
        });
        
        sendResponse({ 
          hasActiveSubscription: result.hasActiveSubscription,
          subscription: result.subscription 
        });
        
      } catch (error) {
        console.error('Errore verifica abbonamento:', error);
        sendResponse({ hasActiveSubscription: false, error: error.message });
      }
    });
    return true;
  }

  if (request.type === "CREATE_CHECKOUT") {
    chrome.storage.local.get('currentUser', (data) => {
      if (!data.currentUser) {
        sendResponse({ success: false, error: 'Utente non autenticato' });
        return;
      }
      
      const processCheckout = async () => {
        try {
          const checkoutData = { 
            userId: data.currentUser,
            email: data.currentUser 
          };
          
          const response = await fetch('https://backend-gdfs.onrender.com/api/create-checkout-session', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(checkoutData)
          });
          
          const result = await response.json();
          
          if (result.url) {
            chrome.tabs.create({ url: result.url });
            sendResponse({ success: true, sessionId: result.sessionId });
          } else {
            sendResponse({ success: false, error: result.error || 'URL checkout non ricevuto' });
          }
          
        } catch (error) {
          console.error('Errore creazione checkout:', error);
          sendResponse({ success: false, error: error.message });
        }
      };
      
      processCheckout();
    });
    return true;
  }
  
  if (request.type === "SAVE_FOLDER_SIZE_WITH_URL") {
    const { folderId, cacheData, urlKey, userEmail } = request;
    saveFolderSizeWithUrl(folderId, cacheData, urlKey, userEmail);
    sendResponse({ success: true });
    return true;
  }
  
  if (request.type === "GET_FOLDER_SIZE_WITH_URL") {
    const { folderId, urlKey, userEmail } = request;
    getFolderSizeWithUrl(folderId, urlKey, userEmail).then(cacheData => {
      sendResponse({ cacheData });
    });
    return true;
  }
  
  if (request.type === "GET_URL_CACHE") {
    const { urlKey, userEmail } = request;
    getUrlCache(urlKey, userEmail).then(cache => {
      sendResponse({ cache });
    });
    return true;
  }
  
  if (request.type === "GET_CACHE_STATS") {
    const { urlKey, userEmail } = request;
    getCacheStats(urlKey, userEmail).then(stats => {
      sendResponse({ stats });
    });
    return true;
  }
  
  if (request.type === "CLEAR_URL_CACHE") {
    const { urlKey, userEmail } = request;
    chrome.storage.local.get(['folderSizeCache', 'currentUser'], (data) => {
      const cache = data.folderSizeCache || {};
      const user = userEmail || data.currentUser || 'default';
      
      if (cache[user] && cache[user][urlKey]) {
        delete cache[user][urlKey];
        chrome.storage.local.set({ folderSizeCache: cache });
      }
      sendResponse({ success: true });
    });
    return true;
  }
  
  if (request.type === "SAVE_FOLDER_SIZE") {
    const { folderId, size, userEmail } = request;
    saveFolderSizeToCache(folderId, size, userEmail);
    sendResponse({ success: true });
    return true;
  }
  
  if (request.type === "GET_FOLDER_SIZE") {
    const { folderId, userEmail } = request;
    getFolderSizeFromCache(folderId, userEmail).then(cacheData => {
      sendResponse({ cacheData });
    });
    return true;
  }
  
  if (request.type === "CLEAR_CACHE") {
    const { userEmail, specificFolder, urlKey } = request;
    
    if (urlKey) {
      chrome.storage.local.get(['folderSizeCache', 'currentUser'], (data) => {
        const cache = data.folderSizeCache || {};
        const user = userEmail || data.currentUser || 'default';
        
        if (cache[user] && cache[user][urlKey]) {
          delete cache[user][urlKey];
          chrome.storage.local.set({ folderSizeCache: cache });
        }
        sendResponse({ success: true });
      });
    } else if (specificFolder) {
      chrome.storage.local.get(['folderSizeCache', 'currentUser'], (data) => {
        const cache = data.folderSizeCache || {};
        const user = userEmail || data.currentUser || 'default';
        
        if (cache[user]) {
          for (const urlKey in cache[user]) {
            if (cache[user][urlKey][specificFolder]) {
              delete cache[user][urlKey][specificFolder];
            }
          }
          chrome.storage.local.set({ folderSizeCache: cache });
        }
        sendResponse({ success: true });
      });
    } else {
      clearFolderSizeCache(userEmail).then(() => {
        sendResponse({ success: true });
      });
    }
    return true;
  }

  if (request.type === "GET_ALL_CACHE") {
    chrome.storage.local.get('folderSizeCache', (data) => {
      sendResponse({ cache: data.folderSizeCache || {} });
    });
    return true;
  }
  
  if (request.type === "CLEANUP_CACHE") {
    cleanupOldCache().then(() => {
      sendResponse({ success: true });
    });
    return true;
  }
  
  return false;
});