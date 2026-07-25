const express = require('express');
const QRCode = require('qrcode');
const pino = require('pino');
const {
    default: makeWASocket,
    useMultiFileAuthState,
    DisconnectReason,
    fetchLatestBaileysVersion,
} = require('@whiskeysockets/baileys');

const AUTH_DIR = process.env.WA_AUTH_DIR || '/app/auth';
const PORT = process.env.PORT || 3000;

const logger = pino({ level: process.env.WA_LOG_LEVEL || 'silent' });

let sock = null;
let latestQrDataUrl = null;
let isConnected = false;

function normalizePhone(raw) {
    let phone = String(raw).replace(/[^0-9]/g, '');
    if (phone.startsWith('0')) {
        phone = '62' + phone.slice(1);
    }
    if (!phone.startsWith('62')) {
        phone = '62' + phone;
    }
    return phone;
}

async function startSocket() {
    const { state, saveCreds } = await useMultiFileAuthState(AUTH_DIR);
    const { version } = await fetchLatestBaileysVersion();

    sock = makeWASocket({
        version,
        auth: state,
        logger,
        printQRInTerminal: false,
    });

    sock.ev.on('creds.update', saveCreds);

    sock.ev.on('connection.update', async (update) => {
        const { connection, lastDisconnect, qr } = update;

        if (qr) {
            latestQrDataUrl = await QRCode.toDataURL(qr);
        }

        if (connection === 'open') {
            isConnected = true;
            latestQrDataUrl = null;
            console.log('[whatsapp] connected');
        }

        if (connection === 'close') {
            isConnected = false;
            const statusCode = lastDisconnect?.error?.output?.statusCode;
            const shouldReconnect = statusCode !== DisconnectReason.loggedOut;
            console.log('[whatsapp] connection closed, reconnect:', shouldReconnect);
            if (shouldReconnect) {
                setTimeout(startSocket, 3000);
            }
        }
    });
}

startSocket().catch((err) => {
    console.error('[whatsapp] failed to start socket', err);
});

const app = express();
app.use(express.json());

app.get('/status', (req, res) => {
    res.json({ connected: isConnected, hasQr: !!latestQrDataUrl });
});

app.get('/qr', (req, res) => {
    if (isConnected) {
        return res.send('<h1>WhatsApp sudah terhubung.</h1>');
    }
    if (!latestQrDataUrl) {
        return res.send('<h1>QR belum siap, refresh beberapa detik lagi...</h1><script>setTimeout(()=>location.reload(),3000)</script>');
    }
    res.send(`
        <html><body style="display:flex;flex-direction:column;align-items:center;font-family:sans-serif;margin-top:40px">
            <h2>Scan QR ini dengan WhatsApp (Perangkat Tertaut)</h2>
            <img src="${latestQrDataUrl}" width="300" height="300" />
            <script>setTimeout(()=>location.reload(),15000)</script>
        </body></html>
    `);
});

app.post('/send', async (req, res) => {
    const { phone, message } = req.body || {};

    if (!phone || !message) {
        return res.status(422).json({ success: false, message: 'phone and message are required' });
    }

    if (!isConnected || !sock) {
        return res.status(503).json({ success: false, message: 'WhatsApp engine is not connected' });
    }

    try {
        const jid = `${normalizePhone(phone)}@s.whatsapp.net`;
        await sock.sendMessage(jid, { text: message });
        res.json({ success: true });
    } catch (err) {
        console.error('[whatsapp] send failed', err);
        res.status(500).json({ success: false, message: err.message });
    }
});

app.listen(PORT, () => console.log(`[whatsapp] engine listening on :${PORT}`));
