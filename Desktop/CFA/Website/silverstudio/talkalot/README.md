# 🤖 Chatbot Management System

Sistema completo per gestire chatbot AI multipli con dashboard admin e widget embeddabile.

## 🚀 Deploy su Railway


### Deploy su Railway

**A. Push su GitHub:**
```bash
git init
git add .
git commit -m "Initial commit"
git remote add origin https://github.com/TUO-USERNAME/chatbot-system.git
git push -u origin main
```

**B. Su Railway.app:**
1. New Project → Deploy from GitHub
2. Seleziona `chatbot-system`
3. Aggiungi PostgreSQL database
4. Aggiungi variabili ambiente:
   - `OPENAI_API_KEY`
   - `ANTHROPIC_API_KEY`
5. Generate Domain

### Accesso

- **API**: `https://tuo-progetto.up.railway.app/`
- **Dashboard**: `https://tuo-progetto.up.railway.app/dashboard`
- **Widget**: `https://tuo-progetto.up.railway.app/widget.js`

## 🔧 Supporto

Per problemi: controlla logs su Railway Dashboard → Deployments → View Logs