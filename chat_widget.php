<style>
    .ai-chat-btn {
        position: fixed;
        bottom: 24px;
        right: 24px;
        width: 66px;
        height: 66px;
        padding: 0;
        border-radius: 50%;
        background: #000000;
        border: none;
        outline: none;
        cursor: pointer;
        overflow: hidden;
        box-shadow: 0 10px 28px rgba(0, 0, 0, 0.55);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 999999;
        transition: transform 0.3s cubic-bezier(0.22, 1, 0.36, 1), box-shadow 0.3s ease;
    }
    .ai-chat-btn:hover {
        transform: scale(1.09);
        box-shadow: 0 14px 34px rgba(0, 0, 0, 0.7);
    }
    .ai-orb-video {
        width: 122%;
        height: 122%;
        object-fit: cover;
        pointer-events: none;
        display: block;
        animation: orbSmoothBreath 6s ease-in-out infinite;
    }
    @keyframes orbSmoothBreath {
        0%, 100% { transform: scale(1) rotate(0deg); }
        50% { transform: scale(1.06) rotate(8deg); }
    }
    .ai-close-overlay {
        position: absolute;
        inset: 0;
        background: rgba(15, 23, 42, 0.78);
        backdrop-filter: blur(8px);
        -webkit-backdrop-filter: blur(8px);
        color: #ffffff;
        font-size: 28px;
        display: flex;
        align-items: center;
        justify-content: center;
        opacity: 0;
        pointer-events: none;
        transition: opacity 0.25s ease;
    }
    .ai-chat-btn.active .ai-close-overlay {
        opacity: 1;
    }

    .ai-chat-box {
        position: fixed;
        bottom: 102px;
        right: 24px;
        width: 382px;
        max-width: calc(100vw - 36px);
        height: 515px;
        max-height: calc(100vh - 130px);
        background: linear-gradient(
            145deg,
            rgba(22, 31, 48, 0.70) 0%,
            rgba(13, 19, 33, 0.76) 100%
        );
        backdrop-filter: blur(22px) saturate(150%);
        -webkit-backdrop-filter: blur(22px) saturate(150%);
        border: 1px solid rgba(255, 255, 255, 0.24);
        border-radius: 22px;
        box-shadow:
            0 24px 60px rgba(0, 0, 0, 0.55),
            inset 0 1px 1px rgba(255, 255, 255, 0.35),
            inset 0 -1px 1px rgba(255, 255, 255, 0.08);
        display: none;
        flex-direction: column;
        overflow: hidden;
        z-index: 999998;
        font-family: inherit;
    }
    .ai-chat-box::before {
        content: "";
        position: absolute;
        inset: 0;
        background: linear-gradient(
            125deg,
            rgba(255, 255, 255, 0.14) 0%,
            rgba(255, 255, 255, 0.03) 36%,
            transparent 60%
        );
        pointer-events: none;
        z-index: 0;
    }
    .ai-chat-box.open {
        display: flex;
        animation: glassPopIn 0.25s cubic-bezier(0.22, 1, 0.36, 1);
    }
    @keyframes glassPopIn {
        from { opacity: 0; transform: translateY(16px) scale(0.96); }
        to { opacity: 1; transform: translateY(0) scale(1); }
    }

    .ai-chat-header {
        position: relative;
        z-index: 2;
        background: rgba(255, 255, 255, 0.06);
        padding: 14px 18px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        border-bottom: 1px solid rgba(255, 255, 255, 0.14);
    }
    .ai-header-info {
        display: flex;
        align-items: center;
        gap: 11px;
    }
    .ai-bot-avatar {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: #000000;
        border: 1px solid rgba(255, 255, 255, 0.22);
        overflow: hidden;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }
    .ai-bot-avatar video {
        width: 122%;
        height: 122%;
        object-fit: cover;
        pointer-events: none;
    }
    .ai-header-text h4 {
        margin: 0;
        font-size: 14.5px;
        font-weight: 600;
        color: #ffffff;
        letter-spacing: 0.2px;
        text-shadow: 0 1px 4px rgba(0, 0, 0, 0.35);
    }
    .ai-status {
        display: flex;
        align-items: center;
        gap: 6px;
        font-size: 11.5px;
        color: rgba(226, 232, 240, 0.85);
        margin-top: 2px;
    }
    .ai-status-dot {
        width: 7px;
        height: 7px;
        border-radius: 50%;
        background: #0abf30;
        box-shadow: 0 0 8px #0abf30;
    }
    .ai-chat-close {
        width: 32px;
        height: 32px;
        border-radius: 10px;
        background: rgba(255, 255, 255, 0.08);
        border: 1px solid rgba(255, 255, 255, 0.12);
        color: #e2e8f0;
        font-size: 20px;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.2s;
    }
    .ai-chat-close:hover {
        background: rgba(239, 68, 68, 0.28);
        color: #ffffff;
    }

    .ai-chat-messages {
        position: relative;
        z-index: 2;
        flex: 1;
        padding: 16px;
        overflow-y: auto;
        display: flex;
        flex-direction: column;
        gap: 12px;
        scrollbar-width: thin;
        scrollbar-color: rgba(255, 255, 255, 0.2) transparent;
    }
    .ai-chat-messages::-webkit-scrollbar {
        width: 5px;
    }
    .ai-chat-messages::-webkit-scrollbar-thumb {
        background: rgba(255, 255, 255, 0.2);
        border-radius: 10px;
    }
    .ai-msg-row {
        display: flex;
        align-items: flex-end;
        gap: 8px;
        animation: msgFadeIn 0.25s ease;
    }
    @keyframes msgFadeIn {
        from { opacity: 0; transform: translateY(6px); }
        to { opacity: 1; transform: translateY(0); }
    }
    .ai-msg-row.user {
        justify-content: flex-end;
    }
    .ai-mini-icon {
        width: 28px;
        height: 28px;
        border-radius: 50%;
        background: #000000;
        border: 1px solid rgba(255, 255, 255, 0.2);
        overflow: hidden;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.45);
    }
    .ai-mini-icon video {
        width: 124%;
        height: 124%;
        object-fit: cover;
        pointer-events: none;
        display: block;
    }
    .ai-msg {
        max-width: 80%;
        padding: 10px 14px;
        border-radius: 16px;
        font-size: 13.5px;
        line-height: 1.5;
        word-wrap: break-word;
    }
    .ai-msg.bot {
        background: rgba(255, 255, 255, 0.11);
        color: #f8fafc;
        border: 1px solid rgba(255, 255, 255, 0.16);
        border-bottom-left-radius: 4px;
        box-shadow: 0 4px 16px rgba(0, 0, 0, 0.18);
    }
    .ai-msg.user {
        background: linear-gradient(135deg, rgba(10, 191, 48, 0.88) 0%, rgba(5, 150, 105, 0.92) 100%);
        color: #ffffff;
        border: 1px solid rgba(255, 255, 255, 0.22);
        border-bottom-right-radius: 4px;
        box-shadow: 0 4px 14px rgba(10, 191, 48, 0.28);
    }

    .ai-typing {
        display: inline-flex;
        gap: 4px;
        padding: 4px 2px;
    }
    .ai-typing span {
        width: 6px;
        height: 6px;
        border-radius: 50%;
        background: #4ade80;
        animation: typingBounce 1.2s infinite ease-in-out;
    }
    .ai-typing span:nth-child(2) { animation-delay: 0.2s; }
    .ai-typing span:nth-child(3) { animation-delay: 0.4s; }
    @keyframes typingBounce {
        0%, 80%, 100% { transform: scale(0.6); opacity: 0.4; }
        40% { transform: scale(1); opacity: 1; }
    }

    .ai-chat-suggestions {
        position: relative;
        z-index: 2;
        padding: 9px 14px;
        display: flex;
        gap: 7px;
        overflow-x: auto;
        border-top: 1px solid rgba(255, 255, 255, 0.1);
        scrollbar-width: none;
    }
    .ai-chat-suggestions::-webkit-scrollbar {
        display: none;
    }
    .ai-chip {
        background: rgba(255, 255, 255, 0.08);
        color: #ecfdf5;
        border: 1px solid rgba(255, 255, 255, 0.18);
        border-radius: 999px;
        padding: 5px 12px;
        font-size: 11.5px;
        font-weight: 500;
        white-space: nowrap;
        cursor: pointer;
        transition: all 0.2s;
    }
    .ai-chip:hover {
        background: rgba(10, 191, 48, 0.28);
        border-color: rgba(74, 222, 128, 0.65);
        transform: translateY(-1px);
    }

    .ai-chat-input-area {
        position: relative;
        z-index: 2;
        display: flex;
        align-items: center;
        padding: 12px 14px;
        background: rgba(255, 255, 255, 0.05);
        border-top: 1px solid rgba(255, 255, 255, 0.14);
        gap: 8px;
    }
    .ai-chat-input-area input {
        flex: 1;
        background: rgba(15, 23, 42, 0.45);
        border: 1px solid rgba(255, 255, 255, 0.2);
        border-radius: 12px;
        padding: 10px 14px;
        color: #ffffff;
        font-size: 13.5px;
        outline: none;
        transition: border-color 0.2s, background 0.2s;
    }
    .ai-chat-input-area input::placeholder {
        color: rgba(226, 232, 240, 0.65);
    }
    .ai-chat-input-area input:focus {
        border-color: rgba(74, 222, 128, 0.7);
        background: rgba(15, 23, 42, 0.62);
    }
    .ai-chat-input-area button {
        width: 40px;
        height: 40px;
        background: linear-gradient(135deg, #0abf30 0%, #059669 100%);
        color: #fff;
        border: 1px solid rgba(255, 255, 255, 0.22);
        border-radius: 12px;
        cursor: pointer;
        font-size: 18px;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: transform 0.2s, box-shadow 0.2s;
    }
    .ai-chat-input-area button:hover {
        transform: scale(1.06);
        box-shadow: 0 4px 12px rgba(10, 191, 48, 0.4);
    }
    .ai-chat-input-area button:disabled,
    .ai-chat-input-area input:disabled {
        opacity: 0.55;
        cursor: not-allowed;
    }
</style>

<button type="button" class="ai-chat-btn" id="aiChatToggle" onclick="toggleChatWindow()" title="Ask NSport Pulse AI">
    <video class="ai-orb-video ai-sync-orb" src="bot_orb.mp4" autoplay loop muted playsinline preload="auto"></video>
    <div class="ai-close-overlay"><i class='bx bx-x'></i></div>
</button>

<div class="ai-chat-box" id="aiChatBox">
    <div class="ai-chat-header">
        <div class="ai-header-info">
            <div class="ai-bot-avatar">
                <video class="ai-sync-orb" src="bot_orb.mp4" autoplay loop muted playsinline preload="auto"></video>
            </div>
            <div class="ai-header-text">
                <h4>NSport Pulse AI</h4>
                <div class="ai-status">
                    <span class="ai-status-dot"></span>
                    <span id="aiQuotaStatus">Online • Live Schedule & Clubs</span>
                </div>
            </div>
        </div>
        <button type="button" class="ai-chat-close" id="aiChatClose" onclick="toggleChatWindow(true)" title="Close"><i class='bx bx-x'></i></button>
    </div>

    <div class="ai-chat-messages" id="aiChatMessages">
        <div class="ai-msg-row bot">
            <div class="ai-mini-icon">
                <video class="ai-sync-orb" src="bot_orb.mp4" autoplay loop muted playsinline preload="auto"></video>
            </div>
            <div class="ai-msg bot">
                Hey there! 👋 I'm <strong>NSport Pulse AI</strong>. Ask me about upcoming matches, sports clubs, or court bookings!
            </div>
        </div>
    </div>

    <div class="ai-chat-suggestions">
        <span class="ai-chip" onclick="sendQuickPrompt('What are the upcoming matches?')">📅 Upcoming Matches</span>
        <span class="ai-chip" onclick="sendQuickPrompt('List available sports clubs')">🏆 Sports Clubs</span>
        <span class="ai-chip" onclick="sendQuickPrompt('How do I book a facility?')">🏟️ Facility Booking</span>
    </div>

    <form class="ai-chat-input-area" id="aiChatForm" onsubmit="submitAiForm(event)">
        <input type="text" id="aiChatInput" maxlength="300" placeholder="Ask NSport Pulse AI..." autocomplete="off" required>
        <button type="submit" id="aiSendBtn" title="Send"><i class='bx bxs-send'></i></button>
    </form>
</div>

<script>
    let isSending = false;

    function attachSeamlessLoop(vid) {
        if (!vid || vid.dataset.loopBound) return;
        vid.dataset.loopBound = '1';
        vid.addEventListener('timeupdate', () => {
            if (vid.duration && vid.currentTime >= vid.duration - 0.18) {
                vid.currentTime = 0.05;
                vid.play().catch(() => {});
            }
        });
        vid.play().catch(() => {});
    }

    document.querySelectorAll('.ai-sync-orb').forEach(attachSeamlessLoop);

    function toggleChatWindow(forceClose = false) {
        const box = document.getElementById('aiChatBox');
        const btn = document.getElementById('aiChatToggle');
        const inp = document.getElementById('aiChatInput');
        if (!box || !btn) return;

        const isOpen = forceClose ? false : !box.classList.contains('open');
        box.classList.toggle('open', isOpen);
        btn.classList.toggle('active', isOpen);

        if (isOpen) {
            document.querySelectorAll('.ai-sync-orb').forEach(v => v.play().catch(() => {}));
            if (inp) setTimeout(() => inp.focus(), 150);
        }
    }

    function escapeHtml(str) {
        return str.replace(/[&<>"']/g, m => ({
            '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
        }[m]));
    }

    function formatBotText(text) {
        return text
            .replace(/\n/g, '<br>')
            .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
            .replace(/^\* /gm, '• ');
    }

    function appendMessage(htmlContent, sender) {
        const msgs = document.getElementById('aiChatMessages');
        const row = document.createElement('div');
        row.className = 'ai-msg-row ' + sender;

        if (sender === 'bot') {
            const icon = document.createElement('div');
            icon.className = 'ai-mini-icon';
            const vid = document.createElement('video');
            vid.className = 'ai-sync-orb';
            vid.src = 'bot_orb.mp4';
            vid.autoplay = true;
            vid.loop = true;
            vid.muted = true;
            vid.playsInline = true;
            vid.preload = 'auto';
            icon.appendChild(vid);
            row.appendChild(icon);
            attachSeamlessLoop(vid);
        }

        const bubble = document.createElement('div');
        bubble.className = 'ai-msg ' + sender;
        bubble.innerHTML = htmlContent;
        row.appendChild(bubble);

        msgs.appendChild(row);
        msgs.scrollTop = msgs.scrollHeight;
        return bubble;
    }

    async function handleSendMessage(message) {
        if (isSending) return;
        isSending = true;

        const msgs = document.getElementById('aiChatMessages');
        const inp = document.getElementById('aiChatInput');
        const sendBtn = document.getElementById('aiSendBtn');
        const quotaLabel = document.getElementById('aiQuotaStatus');

        inp.disabled = true;
        sendBtn.disabled = true;

        appendMessage(escapeHtml(message), 'user');
        const loadingBubble = appendMessage(
            '<div class="ai-typing"><span></span><span></span><span></span></div>',
            'bot'
        );

        try {
            const res = await fetch('assistant_helper.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ message: message })
            });

            const data = await res.json();
            loadingBubble.innerHTML = formatBotText(data.reply || 'Sorry, no response received.');

            if (typeof data.remaining_messages !== 'undefined' && quotaLabel) {
                quotaLabel.textContent = `Online • ${data.remaining_messages} messages left today`;
            }
        } catch (err) {
            loadingBubble.textContent = 'Connection error. Please try again.';
        } finally {
            isSending = false;
            inp.disabled = false;
            sendBtn.disabled = false;
            inp.focus();
            msgs.scrollTop = msgs.scrollHeight;
        }
    }

    function submitAiForm(e) {
        e.preventDefault();
        const inp = document.getElementById('aiChatInput');
        const msg = inp.value.trim();
        if (!msg) return;
        inp.value = '';
        handleSendMessage(msg);
    }

    function sendQuickPrompt(text) {
        handleSendMessage(text);
    }
</script>