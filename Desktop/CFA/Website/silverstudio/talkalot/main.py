from fastapi import FastAPI, HTTPException
from fastapi.middleware.cors import CORSMiddleware
from fastapi.responses import HTMLResponse, Response
from pydantic import BaseModel
from typing import Optional, List
from datetime import datetime
import uuid
import os
from dotenv import load_dotenv
import psycopg2
from psycopg2.extras import RealDictCursor
from contextlib import contextmanager

load_dotenv()

app = FastAPI(title="Chatbot Management API")

app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

DATABASE_URL = os.getenv("DATABASE_URL")

@contextmanager
def get_db():
    conn = psycopg2.connect(DATABASE_URL)
    try:
        yield conn
    finally:
        conn.close()

def init_db():
    with get_db() as conn:
        cursor = conn.cursor()
        
        cursor.execute("""
            CREATE TABLE IF NOT EXISTS chatbots (
                id TEXT PRIMARY KEY,
                name TEXT NOT NULL,
                website TEXT NOT NULL,
                model TEXT NOT NULL,
                system_prompt TEXT,
                welcome_message TEXT,
                primary_color TEXT,
                bot_name TEXT,
                status TEXT DEFAULT 'active',
                conversations INTEGER DEFAULT 0,
                last_activity TEXT,
                created_at TIMESTAMP DEFAULT NOW()
            )
        """)
        
        cursor.execute("""
            CREATE TABLE IF NOT EXISTS conversations (
                id TEXT PRIMARY KEY,
                chatbot_id TEXT NOT NULL,
                session_id TEXT NOT NULL,
                user_message TEXT NOT NULL,
                bot_response TEXT NOT NULL,
                created_at TIMESTAMP DEFAULT NOW(),
                FOREIGN KEY (chatbot_id) REFERENCES chatbots (id) ON DELETE CASCADE
            )
        """)
        
        cursor.execute("CREATE INDEX IF NOT EXISTS idx_chatbots_status ON chatbots(status)")
        cursor.execute("CREATE INDEX IF NOT EXISTS idx_conversations_chatbot ON conversations(chatbot_id)")
        cursor.execute("CREATE INDEX IF NOT EXISTS idx_conversations_session ON conversations(session_id)")
        
        conn.commit()

if DATABASE_URL:
    init_db()

class ChatbotCreate(BaseModel):
    name: str
    website: str
    model: str = "gpt-3.5-turbo"
    systemPrompt: str = ""
    welcomeMessage: str = "Ciao! Come posso aiutarti?"
    primaryColor: str = "#008bb6"
    botName: str = "Assistente"

class ChatbotUpdate(BaseModel):
    name: Optional[str] = None
    website: Optional[str] = None
    model: Optional[str] = None
    systemPrompt: Optional[str] = None
    welcomeMessage: Optional[str] = None
    primaryColor: Optional[str] = None
    botName: Optional[str] = None
    status: Optional[str] = None

class Chatbot(BaseModel):
    id: str
    name: str
    website: str
    model: str
    systemPrompt: str
    welcomeMessage: str
    primaryColor: str
    botName: str
    status: str
    conversations: int
    lastActivity: Optional[str]
    createdAt: str

class Stats(BaseModel):
    totalBots: int
    activeBots: int
    totalConversations: int
    monthlyRevenue: float
    monthlyCost: float

class Message(BaseModel):
    content: str
    chatbotId: str
    sessionId: Optional[str] = None

@app.get("/")
def read_root():
    return {"message": "Chatbot Management API", "version": "1.0.0", "status": "online"}

@app.get("/dashboard")
def serve_dashboard():
    html_content = """<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Talkalot - Dashboard</title>
    <script crossorigin src="https://unpkg.com/react@18/umd/react.production.min.js"></script>
    <script crossorigin src="https://unpkg.com/react-dom@18/umd/react-dom.production.min.js"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/@babel/standalone@7.23.5/babel.min.js"></script>
</head>
<body>
    <div id="root"></div>
    
    <script type="text/babel">
        const { useState, useEffect } = React;
        const API_URL = window.location.origin;
        
        const Plus = (props) => <svg {...props} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>;
        const Settings = (props) => <svg {...props} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><circle cx="12" cy="12" r="3"></circle><path d="M12 1v6m0 6v6m6-15l-4.5 4.5M10.5 13.5L6 18m12-12l-4.5 4.5M10.5 10.5L6 6"></path></svg>;
        const Code = (props) => <svg {...props} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><polyline points="16 18 22 12 16 6"></polyline><polyline points="8 6 2 12 8 18"></polyline></svg>;
        const BarChart3 = (props) => <svg {...props} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><path d="M3 3v18h18"></path><rect x="7" y="10" width="3" height="8"></rect><rect x="14" y="5" width="3" height="13"></rect></svg>;
        const MessageSquare = (props) => <svg {...props} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>;
        const Check = (props) => <svg {...props} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><polyline points="20 6 9 17 4 12"></polyline></svg>;
        const Trash2 = (props) => <svg {...props} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>;
        const Edit2 = (props) => <svg {...props} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><path d="M17 3a2.828 2.828 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5L17 3z"></path></svg>;
        const Home = (props) => <svg {...props} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>;
        const Database = (props) => <svg {...props} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><ellipse cx="12" cy="5" rx="9" ry="3"></ellipse><path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3"></path><path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"></path></svg>;
        const Menu = (props) => <svg {...props} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><line x1="3" y1="12" x2="21" y2="12"></line><line x1="3" y1="6" x2="21" y2="6"></line><line x1="3" y1="18" x2="21" y2="18"></line></svg>;
        const X = (props) => <svg {...props} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>;
        const Activity = (props) => <svg {...props} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline></svg>;
        const DollarSign = (props) => <svg {...props} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><line x1="12" y1="1" x2="12" y2="23"></line><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg>;
        const TrendingUp = (props) => <svg {...props} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"></polyline><polyline points="17 6 23 6 23 12"></polyline></svg>;

        function ChatbotDashboard() {
          const [chatbots, setChatbots] = useState([]);
          const [stats, setStats] = useState({
            totalBots: 0,
            activeBots: 0,
            totalConversations: 0,
            monthlyRevenue: 0,
            monthlyCost: 0
          });
          const [loading, setLoading] = useState(false);
          const [sidebarOpen, setSidebarOpen] = useState(true);
          const [activeView, setActiveView] = useState('dashboard');
          const [showNewChatbot, setShowNewChatbot] = useState(false);
          const [editingChatbot, setEditingChatbot] = useState(null);
          const [copiedId, setCopiedId] = useState(null);
          const [newChatbot, setNewChatbot] = useState({
            name: '',
            website: '',
            model: 'gpt-3.5-turbo',
            systemPrompt: '',
            welcomeMessage: 'Ciao! Come posso aiutarti?',
            primaryColor: '#008bb6',
            botName: 'Assistente'
          });

          useEffect(() => {
            fetchChatbots();
            fetchStats();
          }, []);

          const fetchChatbots = async () => {
            setLoading(true);
            try {
              const response = await fetch(`${API_URL}/api/chatbots`);
              const data = await response.json();
              setChatbots(data);
            } catch (error) {
              console.error('Errore caricamento chatbots:', error);
            } finally {
              setLoading(false);
            }
          };

          const fetchStats = async () => {
            try {
              const response = await fetch(`${API_URL}/api/stats`);
              const data = await response.json();
              setStats(data);
            } catch (error) {
              console.error('Errore caricamento statistiche:', error);
            }
          };

          const handleCreateChatbot = async () => {
            try {
              const response = await fetch(`${API_URL}/api/chatbots`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(newChatbot)
              });
              const data = await response.json();
              setChatbots([...chatbots, data]);
              setNewChatbot({
                name: '',
                website: '',
                model: 'gpt-3.5-turbo',
                systemPrompt: '',
                welcomeMessage: 'Ciao! Come posso aiutarti?',
                primaryColor: '#008bb6',
                botName: 'Assistente'
              });
              setShowNewChatbot(false);
              fetchStats();
            } catch (error) {
              console.error('Errore creazione chatbot:', error);
            }
          };

          const handleUpdateChatbot = async () => {
            try {
              const response = await fetch(`${API_URL}/api/chatbots/${editingChatbot.id}`, {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(editingChatbot)
              });
              const data = await response.json();
              setChatbots(chatbots.map(bot => bot.id === data.id ? data : bot));
              setEditingChatbot(null);
            } catch (error) {
              console.error('Errore aggiornamento chatbot:', error);
            }
          };

          const handleDeleteChatbot = async (id) => {
            if (!confirm('Sei sicuro di voler eliminare questo chatbot?')) return;
            try {
              await fetch(`${API_URL}/api/chatbots/${id}`, { method: 'DELETE' });
              setChatbots(chatbots.filter(bot => bot.id !== id));
              fetchStats();
            } catch (error) {
              console.error('Errore eliminazione chatbot:', error);
            }
          };

          const generateEmbedCode = (chatbotId) => {
            return `<script src="${API_URL}/widget.js" data-chatbot-id="${chatbotId}"></script>`;
          };

          const copyToClipboard = (text, id) => {
            navigator.clipboard.writeText(text);
            setCopiedId(id);
            setTimeout(() => setCopiedId(null), 2000);
          };

          const MenuItem = ({ icon: Icon, label, view, badge }) => (
            <button
              onClick={() => setActiveView(view)}
              className={`w-full flex items-center gap-3 px-4 py-3 rounded-lg transition-colors ${
                activeView === view ? 'bg-[#008bb6] text-white' : 'text-gray-400 hover:bg-gray-800 hover:text-white'
              }`}
            >
              <Icon className="w-5 h-5" />
              <span className="font-medium">{label}</span>
              {badge && <span className="ml-auto bg-gray-700 text-xs px-2 py-1 rounded-full">{badge}</span>}
            </button>
          );

          return (
            <div className="min-h-screen bg-gray-950 flex">
              <aside className={`${sidebarOpen ? 'w-64' : 'w-20'} bg-gray-900 border-r border-gray-800 transition-all duration-300 flex flex-col`}>
                <div className="p-6 border-b border-gray-800 flex items-center justify-between">
                  {sidebarOpen && (
                    <div>
                      <h1 className="text-xl font-bold text-white">Talkalot</h1>
                      <p className="text-xs text-gray-400 mt-1">Admin Dashboard</p>
                    </div>
                  )}
                  <button onClick={() => setSidebarOpen(!sidebarOpen)} className="text-gray-400 hover:text-white p-2 hover:bg-gray-800 rounded-lg">
                    {sidebarOpen ? <X className="w-5 h-5" /> : <Menu className="w-5 h-5" />}
                  </button>
                </div>
                <nav className="flex-1 p-4 space-y-2">
                  <MenuItem icon={Home} label="Dashboard" view="dashboard" />
                  <MenuItem icon={MessageSquare} label="Chatbots" view="chatbots" badge={chatbots.length} />
                  <MenuItem icon={BarChart3} label="Analytics" view="analytics" />
                  <MenuItem icon={Database} label="Conversazioni" view="conversations" />
                  <MenuItem icon={Settings} label="Impostazioni" view="settings" />
                </nav>
              </aside>

              <main className="flex-1 overflow-auto">
                <header className="bg-gray-900 border-b border-gray-800 sticky top-0 z-10">
                  <div className="px-8 py-6">
                    <div className="flex justify-between items-center">
                      <div>
                        <h2 className="text-2xl font-bold text-white">
                          {activeView === 'dashboard' && 'Dashboard'}
                          {activeView === 'chatbots' && 'Gestione Chatbots'}
                          {activeView === 'analytics' && 'Analytics'}
                          {activeView === 'conversations' && 'Conversazioni'}
                          {activeView === 'settings' && 'Impostazioni'}
                        </h2>
                        <p className="text-gray-400 mt-1 text-sm">
                          {activeView === 'dashboard' && 'Panoramica generale del sistema'}
                          {activeView === 'chatbots' && 'Crea e gestisci i tuoi chatbots'}
                        </p>
                      </div>
                      {activeView === 'chatbots' && (
                        <button onClick={() => setShowNewChatbot(true)} className="flex items-center gap-2 bg-[#008bb6] text-white px-5 py-2.5 rounded-lg hover:bg-[#007399] transition-colors font-medium">
                          <Plus className="w-5 h-5" />
                          Nuovo Chatbot
                        </button>
                      )}
                    </div>
                  </div>
                </header>

                <div className="p-8">
                  {activeView === 'dashboard' && (
                    <>
                      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                        <div className="bg-gray-900 border border-gray-800 p-6 rounded-xl">
                          <div className="flex items-center justify-between">
                            <div>
                              <p className="text-sm text-gray-400">Chatbot Attivi</p>
                              <p className="text-3xl font-bold text-white mt-2">{stats.activeBots}</p>
                              <p className="text-xs text-gray-500 mt-1">su {stats.totalBots} totali</p>
                            </div>
                            <div className="bg-[#008bb6] bg-opacity-20 p-3 rounded-lg">
                              <MessageSquare className="text-[#008bb6] w-6 h-6" />
                            </div>
                          </div>
                        </div>
                        <div className="bg-gray-900 border border-gray-800 p-6 rounded-xl">
                          <div className="flex items-center justify-between">
                            <div>
                              <p className="text-sm text-gray-400">Conversazioni</p>
                              <p className="text-3xl font-bold text-white mt-2">{stats.totalConversations}</p>
                              <p className="text-xs text-green-500 mt-1 flex items-center gap-1">
                                <TrendingUp className="w-3 h-3" />
                                +12% questo mese
                              </p>
                            </div>
                            <div className="bg-green-500 bg-opacity-20 p-3 rounded-lg">
                              <Activity className="text-green-500 w-6 h-6" />
                            </div>
                          </div>
                        </div>
                        <div className="bg-gray-900 border border-gray-800 p-6 rounded-xl">
                          <div className="flex items-center justify-between">
                            <div>
                              <p className="text-sm text-gray-400">Ricavi Mese</p>
                              <p className="text-3xl font-bold text-white mt-2">€{stats.monthlyRevenue.toFixed(2)}</p>
                              <p className="text-xs text-gray-500 mt-1">€0.025 per conv.</p>
                            </div>
                            <div className="bg-blue-500 bg-opacity-20 p-3 rounded-lg">
                              <DollarSign className="text-blue-500 w-6 h-6" />
                            </div>
                          </div>
                        </div>
                        <div className="bg-gray-900 border border-gray-800 p-6 rounded-xl">
                          <div className="flex items-center justify-between">
                            <div>
                              <p className="text-sm text-gray-400">Costi API</p>
                              <p className="text-3xl font-bold text-white mt-2">€{stats.monthlyCost.toFixed(2)}</p>
                              <p className="text-xs text-emerald-500 mt-1">
                                Margine: €{(stats.monthlyRevenue - stats.monthlyCost).toFixed(2)}
                              </p>
                            </div>
                            <div className="bg-purple-500 bg-opacity-20 p-3 rounded-lg">
                              <BarChart3 className="text-purple-500 w-6 h-6" />
                            </div>
                          </div>
                        </div>
                      </div>
                      <div className="bg-gray-900 border border-gray-800 rounded-xl p-6">
                        <h3 className="text-lg font-semibold text-white mb-4">Attività Recente</h3>
                        <div className="space-y-3">
                          {chatbots.slice(0, 5).map((bot) => (
                            <div key={bot.id} className="flex items-center justify-between p-3 bg-gray-800 rounded-lg">
                              <div className="flex items-center gap-3">
                                <div className="w-2 h-2 bg-green-500 rounded-full"></div>
                                <div>
                                  <p className="text-white font-medium">{bot.name}</p>
                                  <p className="text-gray-400 text-sm">{bot.website}</p>
                                </div>
                              </div>
                              <p className="text-gray-400 text-sm">{bot.lastActivity || 'N/A'}</p>
                            </div>
                          ))}
                        </div>
                      </div>
                    </>
                  )}

                  {activeView === 'chatbots' && (
                    <div className="bg-gray-900 border border-gray-800 rounded-xl overflow-hidden">
                      {loading ? (
                        <div className="p-12 text-center">
                          <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-[#008bb6] mx-auto"></div>
                          <p className="text-gray-400 mt-4">Caricamento...</p>
                        </div>
                      ) : chatbots.length === 0 ? (
                        <div className="p-12 text-center">
                          <MessageSquare className="mx-auto text-gray-700 mb-4 w-12 h-12" />
                          <p className="text-gray-400 text-lg">Nessun chatbot creato</p>
                          <p className="text-gray-500 text-sm mt-2">Clicca su "Nuovo Chatbot" per iniziare</p>
                        </div>
                      ) : (
                        <div className="divide-y divide-gray-800">
                          {chatbots.map((chatbot) => (
                            <div key={chatbot.id} className="p-6 hover:bg-gray-800 transition-colors">
                              <div className="flex items-start justify-between mb-4">
                                <div>
                                  <div className="flex items-center gap-3 mb-2">
                                    <h3 className="text-lg font-semibold text-white">{chatbot.name}</h3>
                                    <span className={`px-2 py-1 text-xs font-medium rounded-full ${chatbot.status === 'active' ? 'bg-green-500 bg-opacity-20 text-green-400' : 'bg-gray-700 text-gray-400'}`}>
                                      {chatbot.status === 'active' ? 'Attivo' : 'Inattivo'}
                                    </span>
                                  </div>
                                  <p className="text-gray-400 text-sm">{chatbot.website}</p>
                                </div>
                              </div>
                              <div className="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">
                                <div className="bg-gray-800 p-3 rounded-lg">
                                  <p className="text-xs text-gray-400">Conversazioni</p>
                                  <p className="text-lg font-semibold text-white mt-1">{chatbot.conversations || 0}</p>
                                </div>
                                <div className="bg-gray-800 p-3 rounded-lg">
                                  <p className="text-xs text-gray-400">Modello</p>
                                  <p className="text-sm font-medium text-white mt-1">{chatbot.model}</p>
                                </div>
                                <div className="bg-gray-800 p-3 rounded-lg">
                                  <p className="text-xs text-gray-400">Ultima Attività</p>
                                  <p className="text-sm font-medium text-white mt-1">{chatbot.lastActivity || 'N/A'}</p>
                                </div>
                                <div className="bg-gray-800 p-3 rounded-lg">
                                  <p className="text-xs text-gray-400">ID</p>
                                  <p className="text-sm font-mono text-white mt-1">{chatbot.id}</p>
                                </div>
                              </div>
                              <div className="flex flex-wrap gap-2">
                                <button onClick={() => setEditingChatbot({...chatbot})} className="flex items-center gap-2 px-4 py-2 text-sm bg-gray-800 text-gray-300 rounded-lg hover:bg-gray-700 transition-colors border border-gray-700">
                                  <Edit2 className="w-4 h-4" />
                                  Modifica
                                </button>
                                <button onClick={() => {const code = generateEmbedCode(chatbot.id); copyToClipboard(code, chatbot.id);}} className="flex items-center gap-2 px-4 py-2 text-sm bg-[#008bb6] bg-opacity-20 text-[#008bb6] rounded-lg hover:bg-opacity-30 transition-colors border border-[#008bb6] border-opacity-30">
                                  {copiedId === chatbot.id ? <Check className="w-4 h-4" /> : <Code className="w-4 h-4" />}
                                  {copiedId === chatbot.id ? 'Copiato!' : 'Embed Code'}
                                </button>
                                <button onClick={() => handleDeleteChatbot(chatbot.id)} className="flex items-center gap-2 px-4 py-2 text-sm bg-red-500 bg-opacity-20 text-red-400 rounded-lg hover:bg-opacity-30 transition-colors border border-red-500 border-opacity-30">
                                  <Trash2 className="w-4 h-4" />
                                  Elimina
                                </button>
                              </div>
                            </div>
                          ))}
                        </div>
                      )}
                    </div>
                  )}

                  {activeView === 'analytics' && (
                    <div className="bg-gray-900 border border-gray-800 rounded-xl p-8 text-center">
                      <BarChart3 className="mx-auto text-gray-700 mb-4 w-12 h-12" />
                      <p className="text-gray-400 text-lg">Analytics in sviluppo</p>
                    </div>
                  )}

                  {activeView === 'conversations' && (
                    <div className="bg-gray-900 border border-gray-800 rounded-xl p-8 text-center">
                      <Database className="mx-auto text-gray-700 mb-4 w-12 h-12" />
                      <p className="text-gray-400 text-lg">Conversazioni in sviluppo</p>
                    </div>
                  )}

                  {activeView === 'settings' && (
                    <div className="bg-gray-900 border border-gray-800 rounded-xl p-8 text-center">
                      <Settings className="mx-auto text-gray-700 mb-4 w-12 h-12" />
                      <p className="text-gray-400 text-lg">Impostazioni in sviluppo</p>
                    </div>
                  )}
                </div>
              </main>

              {showNewChatbot && (
                <div className="fixed inset-0 bg-black bg-opacity-75 flex items-center justify-center p-4 z-50">
                  <div className="bg-gray-900 border border-gray-800 rounded-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
                    <div className="p-6 border-b border-gray-800">
                      <h2 className="text-xl font-bold text-white">Crea Nuovo Chatbot</h2>
                      <p className="text-gray-400 text-sm mt-1">Configura il nuovo chatbot</p>
                    </div>
                    <div className="p-6 space-y-4">
                      <div>
                        <label className="block text-sm font-medium text-gray-300 mb-2">Nome Cliente *</label>
                        <input type="text" value={newChatbot.name} onChange={(e) => setNewChatbot({...newChatbot, name: e.target.value})} className="w-full px-4 py-2.5 bg-gray-800 border border-gray-700 rounded-lg text-white focus:ring-2 focus:ring-[#008bb6] focus:border-transparent" placeholder="Es: Cliente Rossi SRL" />
                      </div>
                      <div>
                        <label className="block text-sm font-medium text-gray-300 mb-2">Sito Web *</label>
                        <input type="text" value={newChatbot.website} onChange={(e) => setNewChatbot({...newChatbot, website: e.target.value})} className="w-full px-4 py-2.5 bg-gray-800 border border-gray-700 rounded-lg text-white focus:ring-2 focus:ring-[#008bb6] focus:border-transparent" placeholder="www.esempio.com" />
                      </div>
                      <div className="grid grid-cols-2 gap-4">
                        <div>
                          <label className="block text-sm font-medium text-gray-300 mb-2">Modello AI</label>
                          <select value={newChatbot.model} onChange={(e) => setNewChatbot({...newChatbot, model: e.target.value})} className="w-full px-4 py-2.5 bg-gray-800 border border-gray-700 rounded-lg text-white focus:ring-2 focus:ring-[#008bb6] focus:border-transparent">
                            <option value="gpt-3.5-turbo">GPT-3.5 Turbo</option>
                            <option value="gpt-4o-mini">GPT-4o Mini</option>
                            <option value="gpt-4">GPT-4</option>
                            <option value="claude-3-haiku">Claude 3 Haiku</option>
                          </select>
                        </div>
                        <div>
                          <label className="block text-sm font-medium text-gray-300 mb-2">Nome Bot</label>
                          <input type="text" value={newChatbot.botName} onChange={(e) => setNewChatbot({...newChatbot, botName: e.target.value})} className="w-full px-4 py-2.5 bg-gray-800 border border-gray-700 rounded-lg text-white focus:ring-2 focus:ring-[#008bb6] focus:border-transparent" placeholder="Assistente" />
                        </div>
                      </div>
                      <div>
                        <label className="block text-sm font-medium text-gray-300 mb-2">Messaggio di Benvenuto</label>
                        <input type="text" value={newChatbot.welcomeMessage} onChange={(e) => setNewChatbot({...newChatbot, welcomeMessage: e.target.value})} className="w-full px-4 py-2.5 bg-gray-800 border border-gray-700 rounded-lg text-white focus:ring-2 focus:ring-[#008bb6] focus:border-transparent" placeholder="Ciao! Come posso aiutarti?" />
                      </div>
                      <div>
                        <label className="block text-sm font-medium text-gray-300 mb-2">System Prompt</label>
                        <textarea value={newChatbot.systemPrompt} onChange={(e) => setNewChatbot({...newChatbot, systemPrompt: e.target.value})} rows={5} className="w-full px-4 py-2.5 bg-gray-800 border border-gray-700 rounded-lg text-white focus:ring-2 focus:ring-[#008bb6] focus:border-transparent resize-none" placeholder="Sei un assistente virtuale per..." />
                      </div>
                      <div>
                        <label className="block text-sm font-medium text-gray-300 mb-2">Colore Primario</label>
                        <div className="flex gap-3">
                          <input type="color" value={newChatbot.primaryColor} onChange={(e) => setNewChatbot({...newChatbot, primaryColor: e.target.value})} className="w-16 h-12 bg-gray-800 border border-gray-700 rounded-lg cursor-pointer" />
                          <input type="text" value={newChatbot.primaryColor} onChange={(e) => setNewChatbot({...newChatbot, primaryColor: e.target.value})} className="flex-1 px-4 py-2.5 bg-gray-800 border border-gray-700 rounded-lg text-white focus:ring-2 focus:ring-[#008bb6] focus:border-transparent" placeholder="#008bb6" />
                        </div>
                      </div>
                    </div>
                    <div className="p-6 border-t border-gray-800 flex justify-end gap-3">
                      <button onClick={() => setShowNewChatbot(false)} className="px-5 py-2.5 text-gray-300 bg-gray-800 rounded-lg hover:bg-gray-700 transition-colors border border-gray-700">Annulla</button>
                      <button onClick={handleCreateChatbot} disabled={!newChatbot.name || !newChatbot.website} className="px-5 py-2.5 bg-[#008bb6] text-white rounded-lg hover:bg-[#007399] transition-colors disabled:opacity-50 disabled:cursor-not-allowed">Crea Chatbot</button>
                    </div>
                  </div>
                </div>
              )}

              {editingChatbot && (
                <div className="fixed inset-0 bg-black bg-opacity-75 flex items-center justify-center p-4 z-50">
                  <div className="bg-gray-900 border border-gray-800 rounded-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
                    <div className="p-6 border-b border-gray-800">
                      <h2 className="text-xl font-bold text-white">Modifica Chatbot</h2>
                      <p className="text-gray-400 text-sm mt-1">Aggiorna le configurazioni</p>
                    </div>
                    <div className="p-6 space-y-4">
                      <div>
                        <label className="block text-sm font-medium text-gray-300 mb-2">Nome Cliente *</label>
                        <input type="text" value={editingChatbot.name} onChange={(e) => setEditingChatbot({...editingChatbot, name: e.target.value})} className="w-full px-4 py-2.5 bg-gray-800 border border-gray-700 rounded-lg text-white focus:ring-2 focus:ring-[#008bb6] focus:border-transparent" />
                      </div>
                      <div>
                        <label className="block text-sm font-medium text-gray-300 mb-2">Sito Web *</label>
                        <input type="text" value={editingChatbot.website} onChange={(e) => setEditingChatbot({...editingChatbot, website: e.target.value})} className="w-full px-4 py-2.5 bg-gray-800 border border-gray-700 rounded-lg text-white focus:ring-2 focus:ring-[#008bb6] focus:border-transparent" />
                      </div>
                      <div className="grid grid-cols-2 gap-4">
                        <div>
                          <label className="block text-sm font-medium text-gray-300 mb-2">Modello AI</label>
                          <select value={editingChatbot.model} onChange={(e) => setEditingChatbot({...editingChatbot, model: e.target.value})} className="w-full px-4 py-2.5 bg-gray-800 border border-gray-700 rounded-lg text-white focus:ring-2 focus:ring-[#008bb6] focus:border-transparent">
                            <option value="gpt-3.5-turbo">GPT-3.5 Turbo</option>
                            <option value="gpt-4o-mini">GPT-4o Mini</option>
                            <option value="gpt-4">GPT-4</option>
                            <option value="claude-3-haiku">Claude 3 Haiku</option>
                          </select>
                        </div>
                        <div>
                          <label className="block text-sm font-medium text-gray-300 mb-2">Nome Bot</label>
                          <input type="text" value={editingChatbot.botName || 'Assistente'} onChange={(e) => setEditingChatbot({...editingChatbot, botName: e.target.value})} className="w-full px-4 py-2.5 bg-gray-800 border border-gray-700 rounded-lg text-white focus:ring-2 focus:ring-[#008bb6] focus:border-transparent" />
                        </div>
                      </div>
                      <div>
                        <label className="block text-sm font-medium text-gray-300 mb-2">Messaggio di Benvenuto</label>
                        <input type="text" value={editingChatbot.welcomeMessage} onChange={(e) => setEditingChatbot({...editingChatbot, welcomeMessage: e.target.value})} className="w-full px-4 py-2.5 bg-gray-800 border border-gray-700 rounded-lg text-white focus:ring-2 focus:ring-[#008bb6] focus:border-transparent" />
                      </div>
                      <div>
                        <label className="block text-sm font-medium text-gray-300 mb-2">System Prompt</label>
                        <textarea value={editingChatbot.systemPrompt} onChange={(e) => setEditingChatbot({...editingChatbot, systemPrompt: e.target.value})} rows={5} className="w-full px-4 py-2.5 bg-gray-800 border border-gray-700 rounded-lg text-white focus:ring-2 focus:ring-[#008bb6] focus:border-transparent resize-none" />
                      </div>
                      <div>
                        <label className="block text-sm font-medium text-gray-300 mb-2">Colore Primario</label>
                        <div className="flex gap-3">
                          <input type="color" value={editingChatbot.primaryColor || '#008bb6'} onChange={(e) => setEditingChatbot({...editingChatbot, primaryColor: e.target.value})} className="w-16 h-12 bg-gray-800 border border-gray-700 rounded-lg cursor-pointer" />
                          <input type="text" value={editingChatbot.primaryColor || '#008bb6'} onChange={(e) => setEditingChatbot({...editingChatbot, primaryColor: e.target.value})} className="flex-1 px-4 py-2.5 bg-gray-800 border border-gray-700 rounded-lg text-white focus:ring-2 focus:ring-[#008bb6] focus:border-transparent" />
                        </div>
                      </div>
                      <div>
                        <label className="block text-sm font-medium text-gray-300 mb-2">Stato</label>
                        <select value={editingChatbot.status} onChange={(e) => setEditingChatbot({...editingChatbot, status: e.target.value})} className="w-full px-4 py-2.5 bg-gray-800 border border-gray-700 rounded-lg text-white focus:ring-2 focus:ring-[#008bb6] focus:border-transparent">
                          <option value="active">Attivo</option>
                          <option value="inactive">Inattivo</option>
                        </select>
                      </div>
                    </div>
                    <div className="p-6 border-t border-gray-800 flex justify-end gap-3">
                      <button onClick={() => setEditingChatbot(null)} className="px-5 py-2.5 text-gray-300 bg-gray-800 rounded-lg hover:bg-gray-700 transition-colors border border-gray-700">Annulla</button>
                      <button onClick={handleUpdateChatbot} className="px-5 py-2.5 bg-[#008bb6] text-white rounded-lg hover:bg-[#007399] transition-colors">Salva Modifiche</button>
                    </div>
                  </div>
                </div>
              )}
            </div>
          );
        }

        const root = ReactDOM.createRoot(document.getElementById('root'));
        root.render(<ChatbotDashboard />);
    </script>
</body>
</html>"""
    return HTMLResponse(content=html_content, media_type="text/html; charset=utf-8")

@app.get("/widget.js")
def serve_widget():
    js_content = """(function() {
    'use strict';
    const API_URL = window.location.origin;
    const script = document.currentScript;
    const chatbotId = script.getAttribute('data-chatbot-id');
    
    if (!chatbotId) {
        console.error('Chatbot ID mancante');
        return;
    }
    
    let sessionId = localStorage.getItem(`chatbot_session_${chatbotId}`);
    if (!sessionId) {
        sessionId = generateUUID();
        localStorage.setItem(`chatbot_session_${chatbotId}`, sessionId);
    }
    
    let chatbotConfig = null;
    let isOpen = false;
    let isLoading = false;
    
    function generateUUID() {
        return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(c) {
            const r = Math.random() * 16 | 0;
            const v = c === 'x' ? r : (r & 0x3 | 0x8);
            return v.toString(16);
        });
    }
    
    async function loadChatbotConfig() {
        try {
            const response = await fetch(`${API_URL}/api/chatbots/${chatbotId}`);
            if (!response.ok) throw new Error('Chatbot non trovato');
            chatbotConfig = await response.json();
            initWidget();
        } catch (error) {
            console.error('Errore caricamento chatbot:', error);
        }
    }
    
    function initWidget() {
        const color = chatbotConfig.primaryColor || '#008bb6';
        const styles = `#chatbot-container * {box-sizing: border-box;margin: 0;padding: 0;}#chatbot-button {position: fixed;bottom: 20px;right: 20px;width: 60px;height: 60px;border-radius: 50%;background: ${color};border: none;cursor: pointer;box-shadow: 0 4px 12px rgba(0,0,0,0.15);z-index: 9998;display: flex;align-items: center;justify-content: center;transition: transform 0.3s, box-shadow 0.3s;}#chatbot-button:hover {transform: scale(1.1);box-shadow: 0 6px 16px rgba(0,0,0,0.2);}#chatbot-button svg {width: 28px;height: 28px;fill: white;}#chatbot-window {position: fixed;bottom: 90px;right: 20px;width: 380px;height: 600px;max-height: calc(100vh - 120px);background: white;border-radius: 16px;box-shadow: 0 8px 32px rgba(0,0,0,0.2);display: none;flex-direction: column;z-index: 9999;font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;}#chatbot-window.open {display: flex;}#chatbot-header {background: ${color};color: white;padding: 20px;border-radius: 16px 16px 0 0;display: flex;justify-content: space-between;align-items: center;}#chatbot-header h3 {font-size: 18px;font-weight: 600;}#chatbot-header p {font-size: 12px;opacity: 0.9;margin-top: 4px;}#chatbot-close {background: transparent;border: none;color: white;cursor: pointer;font-size: 24px;width: 32px;height: 32px;display: flex;align-items: center;justify-content: center;border-radius: 8px;transition: background 0.2s;}#chatbot-close:hover {background: rgba(255,255,255,0.2);}#chatbot-messages {flex: 1;overflow-y: auto;padding: 20px;background: #f8f9fa;}.chatbot-message {margin-bottom: 16px;display: flex;gap: 8px;animation: fadeIn 0.3s;}@keyframes fadeIn {from { opacity: 0; transform: translateY(10px); }to { opacity: 1; transform: translateY(0); }}.chatbot-message.bot {justify-content: flex-start;}.chatbot-message.user {justify-content: flex-end;}.chatbot-avatar {width: 32px;height: 32px;border-radius: 50%;background: ${color};display: flex;align-items: center;justify-content: center;flex-shrink: 0;}.chatbot-avatar svg {width: 18px;height: 18px;fill: white;}.chatbot-bubble {max-width: 70%;padding: 12px 16px;border-radius: 12px;font-size: 14px;line-height: 1.5;word-wrap: break-word;}.chatbot-message.bot .chatbot-bubble {background: white;color: #333;border-bottom-left-radius: 4px;}.chatbot-message.user .chatbot-bubble {background: ${color};color: white;border-bottom-right-radius: 4px;}.chatbot-typing {display: flex;gap: 4px;padding: 12px 16px;}.chatbot-typing span {width: 8px;height: 8px;border-radius: 50%;background: ${color};opacity: 0.4;animation: typing 1.4s infinite;}.chatbot-typing span:nth-child(2) {animation-delay: 0.2s;}.chatbot-typing span:nth-child(3) {animation-delay: 0.4s;}@keyframes typing {0%, 60%, 100% { opacity: 0.4; transform: scale(1); }30% { opacity: 1; transform: scale(1.2); }}#chatbot-input-area {padding: 16px;background: white;border-top: 1px solid #e0e0e0;border-radius: 0 0 16px 16px;}#chatbot-input-form {display: flex;gap: 8px;}#chatbot-input {flex: 1;border: 1px solid #e0e0e0;border-radius: 24px;padding: 12px 16px;font-size: 14px;outline: none;transition: border-color 0.2s;}#chatbot-input:focus {border-color: ${color};}#chatbot-send {width: 44px;height: 44px;border-radius: 50%;background: ${color};border: none;cursor: pointer;display: flex;align-items: center;justify-content: center;transition: opacity 0.2s;}#chatbot-send:hover:not(:disabled) {opacity: 0.9;}#chatbot-send:disabled {opacity: 0.5;cursor: not-allowed;}#chatbot-send svg {width: 20px;height: 20px;fill: white;}@media (max-width: 480px) {#chatbot-window {width: calc(100vw - 20px);height: calc(100vh - 100px);right: 10px;bottom: 80px;}}`;
        
        const styleSheet = document.createElement('style');
        styleSheet.textContent = styles;
        document.head.appendChild(styleSheet);
        
        const container = document.createElement('div');
        container.id = 'chatbot-container';
        container.innerHTML = `<button id="chatbot-button" aria-label="Apri chat"><svg viewBox="0 0 24 24"><path d="M20 2H4c-1.1 0-2 .9-2 2v18l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm0 14H6l-2 2V4h16v12z"/></svg></button><div id="chatbot-window"><div id="chatbot-header"><div><h3>${chatbotConfig.botName || 'Assistente'}</h3><p>Online ora</p></div><button id="chatbot-close" aria-label="Chiudi chat">×</button></div><div id="chatbot-messages"></div><div id="chatbot-input-area"><form id="chatbot-input-form"><input type="text" id="chatbot-input" placeholder="Scrivi un messaggio..." autocomplete="off" /><button type="submit" id="chatbot-send" aria-label="Invia"><svg viewBox="0 0 24 24"><path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/></svg></button></form></div></div>`;
        
        document.body.appendChild(container);
        
        const button = document.getElementById('chatbot-button');
        const window = document.getElementById('chatbot-window');
        const closeBtn = document.getElementById('chatbot-close');
        const form = document.getElementById('chatbot-input-form');
        
        button.addEventListener('click', toggleChat);
        closeBtn.addEventListener('click', toggleChat);
        form.addEventListener('submit', handleSubmit);
        
        if (chatbotConfig.welcomeMessage) {
            addMessage(chatbotConfig.welcomeMessage, 'bot');
        }
    }
    
    function toggleChat() {
        isOpen = !isOpen;
        const window = document.getElementById('chatbot-window');
        window.classList.toggle('open', isOpen);
        if (isOpen) {
            document.getElementById('chatbot-input').focus();
        }
    }
    
    async function handleSubmit(e) {
        e.preventDefault();
        const input = document.getElementById('chatbot-input');
        const message = input.value.trim();
        if (!message || isLoading) return;
        
        addMessage(message, 'user');
        input.value = '';
        isLoading = true;
        document.getElementById('chatbot-send').disabled = true;
        showTyping();
        
        try {
            const response = await fetch(`${API_URL}/api/chat`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({content: message, chatbotId: chatbotId, sessionId: sessionId})
            });
            if (!response.ok) throw new Error('Errore nella risposta');
            const data = await response.json();
            hideTyping();
            addMessage(data.response, 'bot');
        } catch (error) {
            hideTyping();
            addMessage('Scusa, si è verificato un errore. Riprova.', 'bot');
            console.error('Errore invio messaggio:', error);
        } finally {
            isLoading = false;
            document.getElementById('chatbot-send').disabled = false;
        }
    }
    
    function addMessage(text, sender) {
        const messagesContainer = document.getElementById('chatbot-messages');
        const messageDiv = document.createElement('div');
        messageDiv.className = `chatbot-message ${sender}`;
        if (sender === 'bot') {
            messageDiv.innerHTML = `<div class="chatbot-avatar"><svg viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 3c1.66 0 3 1.34 3 3s-1.34 3-3 3-3-1.34-3-3 1.34-3 3-3zm0 14.2c-2.5 0-4.71-1.28-6-3.22.03-1.99 4-3.08 6-3.08 1.99 0 5.97 1.09 6 3.08-1.29 1.94-3.5 3.22-6 3.22z"/></svg></div><div class="chatbot-bubble">${escapeHtml(text)}</div>`;
        } else {
            messageDiv.innerHTML = `<div class="chatbot-bubble">${escapeHtml(text)}</div>`;
        }
        messagesContainer.appendChild(messageDiv);
        messagesContainer.scrollTop = messagesContainer.scrollHeight;
    }
    
    function showTyping() {
        const messagesContainer = document.getElementById('chatbot-messages');
        const typingDiv = document.createElement('div');
        typingDiv.className = 'chatbot-message bot';
        typingDiv.id = 'chatbot-typing-indicator';
        typingDiv.innerHTML = `<div class="chatbot-avatar"><svg viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 3c1.66 0 3 1.34 3 3s-1.34 3-3 3-3-1.34-3-3 1.34-3 3-3zm0 14.2c-2.5 0-4.71-1.28-6-3.22.03-1.99 4-3.08 6-3.08 1.99 0 5.97 1.09 6 3.08-1.29 1.94-3.5 3.22-6 3.22z"/></svg></div><div class="chatbot-bubble"><div class="chatbot-typing"><span></span><span></span><span></span></div></div>`;
        messagesContainer.appendChild(typingDiv);
        messagesContainer.scrollTop = messagesContainer.scrollHeight;
    }
    
    function hideTyping() {
        const typingIndicator = document.getElementById('chatbot-typing-indicator');
        if (typingIndicator) typingIndicator.remove();
    }
    
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    loadChatbotConfig();
})();"""
    return Response(content=js_content, media_type="application/javascript")

@app.get("/api/chatbots", response_model=List[Chatbot])
def get_chatbots():
    with get_db() as conn:
        cursor = conn.cursor(cursor_factory=RealDictCursor)
        cursor.execute("SELECT * FROM chatbots ORDER BY created_at DESC")
        rows = cursor.fetchall()
        chatbots = []
        for row in rows:
            chatbots.append({
                "id": row["id"], "name": row["name"], "website": row["website"],
                "model": row["model"], "systemPrompt": row["system_prompt"] or "",
                "welcomeMessage": row["welcome_message"], "primaryColor": row["primary_color"],
                "botName": row["bot_name"], "status": row["status"],
                "conversations": row["conversations"], "lastActivity": row["last_activity"],
                "createdAt": str(row["created_at"])
            })
        return chatbots

@app.get("/api/chatbots/{chatbot_id}", response_model=Chatbot)
def get_chatbot(chatbot_id: str):
    with get_db() as conn:
        cursor = conn.cursor(cursor_factory=RealDictCursor)
        cursor.execute("SELECT * FROM chatbots WHERE id = %s", (chatbot_id,))
        row = cursor.fetchone()
        if not row:
            raise HTTPException(status_code=404, detail="Chatbot non trovato")
        return {
            "id": row["id"], "name": row["name"], "website": row["website"],
            "model": row["model"], "systemPrompt": row["system_prompt"] or "",
            "welcomeMessage": row["welcome_message"], "primaryColor": row["primary_color"],
            "botName": row["bot_name"], "status": row["status"],
            "conversations": row["conversations"], "lastActivity": row["last_activity"],
            "createdAt": str(row["created_at"])
        }

@app.post("/api/chatbots", response_model=Chatbot)
def create_chatbot(chatbot: ChatbotCreate):
    chatbot_id = str(uuid.uuid4())[:8]
    created_at = datetime.now()
    with get_db() as conn:
        cursor = conn.cursor(cursor_factory=RealDictCursor)
        cursor.execute("""
            INSERT INTO chatbots (id, name, website, model, system_prompt, 
                                 welcome_message, primary_color, bot_name, 
                                 status, conversations, created_at)
            VALUES (%s, %s, %s, %s, %s, %s, %s, %s, 'active', 0, %s)
            RETURNING *
        """, (chatbot_id, chatbot.name, chatbot.website, chatbot.model,
              chatbot.systemPrompt, chatbot.welcomeMessage, chatbot.primaryColor,
              chatbot.botName, created_at))
        row = cursor.fetchone()
        conn.commit()
    return {
        "id": row["id"], "name": row["name"], "website": row["website"],
        "model": row["model"], "systemPrompt": row["system_prompt"] or "",
        "welcomeMessage": row["welcome_message"], "primaryColor": row["primary_color"],
        "botName": row["bot_name"], "status": row["status"],
        "conversations": row["conversations"], "lastActivity": row["last_activity"],
        "createdAt": str(row["created_at"])
    }

@app.put("/api/chatbots/{chatbot_id}", response_model=Chatbot)
def update_chatbot(chatbot_id: str, chatbot: ChatbotUpdate):
    with get_db() as conn:
        cursor = conn.cursor(cursor_factory=RealDictCursor)
        cursor.execute("SELECT * FROM chatbots WHERE id = %s", (chatbot_id,))
        existing = cursor.fetchone()
        if not existing:
            raise HTTPException(status_code=404, detail="Chatbot non trovato")
        
        update_fields = []
        params = []
        if chatbot.name is not None:
            update_fields.append("name = %s")
            params.append(chatbot.name)
        if chatbot.website is not None:
            update_fields.append("website = %s")
            params.append(chatbot.website)
        if chatbot.model is not None:
            update_fields.append("model = %s")
            params.append(chatbot.model)
        if chatbot.systemPrompt is not None:
            update_fields.append("system_prompt = %s")
            params.append(chatbot.systemPrompt)
        if chatbot.welcomeMessage is not None:
            update_fields.append("welcome_message = %s")
            params.append(chatbot.welcomeMessage)
        if chatbot.primaryColor is not None:
            update_fields.append("primary_color = %s")
            params.append(chatbot.primaryColor)
        if chatbot.botName is not None:
            update_fields.append("bot_name = %s")
            params.append(chatbot.botName)
        if chatbot.status is not None:
            update_fields.append("status = %s")
            params.append(chatbot.status)
        
        if update_fields:
            params.append(chatbot_id)
            query = f"UPDATE chatbots SET {', '.join(update_fields)} WHERE id = %s RETURNING *"
            cursor.execute(query, params)
            row = cursor.fetchone()
            conn.commit()
        else:
            cursor.execute("SELECT * FROM chatbots WHERE id = %s", (chatbot_id,))
            row = cursor.fetchone()
        
        return {
            "id": row["id"], "name": row["name"], "website": row["website"],
            "model": row["model"], "systemPrompt": row["system_prompt"] or "",
            "welcomeMessage": row["welcome_message"], "primaryColor": row["primary_color"],
            "botName": row["bot_name"], "status": row["status"],
            "conversations": row["conversations"], "lastActivity": row["last_activity"],
            "createdAt": str(row["created_at"])
        }

@app.delete("/api/chatbots/{chatbot_id}")
def delete_chatbot(chatbot_id: str):
    with get_db() as conn:
        cursor = conn.cursor()
        cursor.execute("SELECT * FROM chatbots WHERE id = %s", (chatbot_id,))
        if not cursor.fetchone():
            raise HTTPException(status_code=404, detail="Chatbot non trovato")
        cursor.execute("DELETE FROM conversations WHERE chatbot_id = %s", (chatbot_id,))
        cursor.execute("DELETE FROM chatbots WHERE id = %s", (chatbot_id,))
        conn.commit()
    return {"message": "Chatbot eliminato con successo"}

@app.get("/api/stats", response_model=Stats)
def get_stats():
    with get_db() as conn:
        cursor = conn.cursor(cursor_factory=RealDictCursor)
        cursor.execute("SELECT COUNT(*) as total FROM chatbots")
        total_bots = cursor.fetchone()["total"]
        cursor.execute("SELECT COUNT(*) as active FROM chatbots WHERE status = 'active'")
        active_bots = cursor.fetchone()["active"]
        cursor.execute("SELECT COALESCE(SUM(conversations), 0) as total FROM chatbots")
        total_conversations = cursor.fetchone()["total"]
        monthly_revenue = float(total_conversations) * 0.025
        monthly_cost = float(total_conversations) * 0.003
        return {
            "totalBots": total_bots,
            "activeBots": active_bots,
            "totalConversations": total_conversations,
            "monthlyRevenue": round(monthly_revenue, 2),
            "monthlyCost": round(monthly_cost, 2)
        }

@app.post("/api/chat")
async def chat(message: Message):
    with get_db() as conn:
        cursor = conn.cursor(cursor_factory=RealDictCursor)
        cursor.execute("SELECT * FROM chatbots WHERE id = %s", (message.chatbotId,))
        chatbot = cursor.fetchone()
        if not chatbot:
            raise HTTPException(status_code=404, detail="Chatbot non trovato")
        if chatbot["status"] != "active":
            raise HTTPException(status_code=403, detail="Chatbot non attivo")
        session_id = message.sessionId or str(uuid.uuid4())
        cursor.execute("""
            SELECT user_message, bot_response 
            FROM conversations 
            WHERE chatbot_id = %s AND session_id = %s
            ORDER BY created_at ASC 
            LIMIT 10
        """, (message.chatbotId, session_id))
        history = [{"user": row["user_message"], "bot": row["bot_response"]} for row in cursor.fetchall()]
    
    bot_response = f"Ciao! Hai scritto: '{message.content}'. Questo è un messaggio di test. Il chatbot AI sarà attivo dopo la configurazione delle API keys."
    conversation_id = str(uuid.uuid4())
    
    with get_db() as conn:
        cursor = conn.cursor()
        cursor.execute("""
            INSERT INTO conversations (id, chatbot_id, session_id, user_message, 
                                      bot_response, created_at)
            VALUES (%s, %s, %s, %s, %s, %s)
        """, (conversation_id, message.chatbotId, session_id, message.content,
              bot_response, datetime.now()))
        cursor.execute("""
            UPDATE chatbots 
            SET conversations = conversations + 1,
                last_activity = %s
            WHERE id = %s
        """, (datetime.now().strftime("%Y-%m-%d %H:%M"), message.chatbotId))
        conn.commit()
    
    return {
        "response": bot_response,
        "sessionId": session_id,
        "chatbotId": message.chatbotId
    }

@app.get("/api/conversations/{chatbot_id}")
def get_conversations(chatbot_id: str, limit: int = 50):
    with get_db() as conn:
        cursor = conn.cursor(cursor_factory=RealDictCursor)
        cursor.execute("""
            SELECT * FROM conversations 
            WHERE chatbot_id = %s 
            ORDER BY created_at DESC 
            LIMIT %s
        """, (chatbot_id, limit))
        rows = cursor.fetchall()
        conversations = []
        for row in rows:
            conversations.append({
                "id": row["id"],
                "chatbotId": row["chatbot_id"],
                "sessionId": row["session_id"],
                "userMessage": row["user_message"],
                "botResponse": row["bot_response"],
                "createdAt": str(row["created_at"])
            })
        return conversations

if __name__ == "__main__":
    import uvicorn
    port = int(os.getenv("PORT", 8000))
    uvicorn.run(app, host="0.0.0.0", port=port)