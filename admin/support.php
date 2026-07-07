<!-- admin/support.php -->
<!DOCTYPE html>
<html class="light" lang="en">
<head>
    <title>SkinMate Admin - Support</title>
    <?php include 'sidebar.php'; ?>
    <script src="https://js.pusher.com/8.0.1/pusher.min.js"></script>
</head>
<body class="bg-surface text-on-surface antialiased min-h-screen flex overflow-hidden">

    <main class="ml-72 flex-1 flex flex-col min-w-0 bg-surface h-screen overflow-hidden pt-[64px]">
        <div class="flex-1 flex overflow-hidden w-full h-full">
            
            <!-- Left Sidebar Section: Active/Closed Tickets Queue -->
            <section class="w-80 border-r border-stone-200/60 flex flex-col bg-surface-container-low flex-shrink-0">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-6">
                        <h2 id="queue-title-label" class="text-xl font-bold text-on-surface">Active Tickets</h2>
                        <span id="label-queue-count" class="bg-primary/10 text-primary px-2.5 py-0.5 rounded-full text-xs font-bold">0</span>
                    </div>
                    <div class="flex gap-2 overflow-x-auto pb-2 no-scrollbar">
                        <!-- Added explicit event hooks and distinct styling IDs -->
                        <span id="tab-pending" onclick="switchQueueTab('open')" class="bg-primary text-on-primary px-3 py-1 rounded-full text-xs whitespace-nowrap cursor-pointer transition-all">Pending</span>
                        <span id="tab-history" onclick="switchQueueTab('closed')" class="bg-surface-container-lowest text-on-surface-variant border border-outline-variant/20 px-3 py-1 rounded-full text-xs whitespace-nowrap cursor-pointer hover:bg-white transition-all">History</span>
                    </div>
                </div>
                
                <div id="admin-ticket-queue" class="flex-1 overflow-y-auto px-3 space-y-2 pb-24 no-scrollbar">
                    <p class="text-xs text-stone-400 text-center mt-4">Loading active sessions...</p>
                </div>
            </section>

            <!-- Center Chat Section -->
            <section class="flex-1 flex flex-col bg-surface-container-lowest min-w-0">
                <div class="p-6 border-b border-stone-100 flex items-center justify-between bg-white flex-shrink-0">
                    <div class="flex items-center gap-4">
                        <div class="relative">
                            <div id="header-user-avatar-placeholder" class="w-12 h-12 rounded-2xl bg-primary/10 text-primary border border-primary/20 flex items-center justify-center text-lg font-black tracking-wide shadow-sm uppercase select-none">
                                -
                            </div>
                            <div id="header-user-status-dot" class="absolute -bottom-1 -right-1 w-4 h-4 bg-green-500 border-2 border-white rounded-full"></div>
                        </div>
                        <div>
                            <h2 id="header-user-name" class="text-lg font-bold">Select a Room</h2>
                            <p class="text-xs text-on-surface-variant flex items-center gap-1">
                                <span class="w-2 h-2 rounded-full bg-green-500/40 animate-pulse"></span>
                                <span id="header-room-id-label">Waiting for connection...</span>
                            </p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <!-- <button class="p-2 rounded-xl hover:bg-surface-container-low text-stone-500 transition-colors">
                            <span class="material-symbols-outlined" data-icon="videocam">videocam</span>
                        </button>
                        <button class="p-2 rounded-xl hover:bg-surface-container-low text-stone-500 transition-colors">
                            <span class="material-symbols-outlined" data-icon="call">call</span>
                        </button> -->
                        <button id="btn-resolve-ticket" onclick="resolveCurrentActiveTicket()" class="px-4 py-2 bg-stone-100 rounded-full text-xs font-bold text-stone-600 hover:bg-stone-200 transition-colors">
                            Resolve Ticket
                        </button>
                    </div>
                </div>

                <div id="admin-chat-canvas" class="flex-1 overflow-y-auto p-8 space-y-6 bg-stone-50/30">
                    <div class="flex flex-col items-center justify-center h-full text-center text-stone-400">
                        <span class="material-symbols-outlined text-4xl mb-2" data-icon="forum">forum</span>
                        <p class="text-sm font-semibold">No ongoing chat selected</p>
                        <p class="text-xs max-w-xs mt-1">Please select an active ticket entry row from the left panel sidebar column queue to view messages.</p>
                    </div>
                </div>

                <div class="p-6 border-t border-stone-100 bg-white flex-shrink-0 mb-4">
                    <div class="bg-surface-container-low rounded-3xl p-2 flex items-end gap-2 focus-within:bg-white focus-within:shadow-md transition-all">
                        <!-- <button class="p-2 text-stone-400 hover:text-primary transition-colors">
                            <span class="material-symbols-outlined" data-icon="add_circle">add_circle</span>
                        </button> -->
                        <!-- <textarea id="admin-reply-input" class="flex-1 bg-transparent border-none focus:ring-0 text-sm py-2 resize-none outline-none" placeholder="Type your reply here..." rows="1" onkeydown="if(event.key === 'Enter') { event.preventDefault(); sendAdminMessageText(); }"></textarea> -->
                        <textarea id="admin-reply-input" class="flex-1 bg-transparent border-none focus:ring-0 text-sm py-2 resize-none outline-none transition-all" placeholder="Type your reply here..." rows="1" onkeydown="if(event.key === 'Enter' && !this.disabled) { event.preventDefault(); sendAdminMessageText(); }"></textarea>
                        <!-- <button class="p-2 text-stone-400 hover:text-primary transition-colors">
                            <span class="material-symbols-outlined" data-icon="mood">mood</span>
                        </button> -->
                        <div class="relative">
                            <button id="emoji-trigger-btn" type="button" class="p-2 text-stone-400 hover:text-primary transition-colors focus:outline-none">
                                <span class="material-symbols-outlined" data-icon="mood">mood</span>
                            </button>
                        </div>
                        <button onclick="sendAdminMessageText()" class="bg-primary text-on-primary w-10 h-10 rounded-full flex items-center justify-center hover:scale-105 active:scale-95 transition-all shadow-md shadow-primary/20">
                            <span class="material-symbols-outlined text-sm" data-icon="send">send</span>
                        </button>
                    </div>
                </div>
            </section>

            <!-- Right Sidebar Section: Customer Meta Details -->
            <section class="w-80 border-l border-stone-200/60 bg-surface flex flex-col p-6 overflow-y-auto flex-shrink-0 no-scrollbar pb-24">
                <h3 class="text-xs font-bold text-stone-400 uppercase tracking-widest mb-6">User Context</h3>
                <div class="text-center mb-8">
                    <div class="relative inline-block mb-4">
                        <div id="context-user-avatar" class="w-24 h-24 rounded-full bg-primary text-on-primary flex items-center justify-center text-3xl font-black border-4 border-white shadow-xl mx-auto uppercase select-none">
                            -
                        </div>
                    </div>
                    <h4 id="context-user-name" class="text-lg font-bold">-</h4>
                    <p id="context-user-joined" class="text-xs text-on-surface-variant">Gathering details...</p>
                </div>
                
                <div class="grid grid-cols-2 gap-3 mb-8">
                    <div class="bg-surface-container-low p-3 rounded-2xl text-center">
                        <span class="text-[10px] text-stone-400 uppercase block mb-1">Lifetime</span>
                        <span id="context-user-lifetime" class="font-bold text-primary">RM 0.00</span>
                    </div>
                    <div class="bg-surface-container-low p-3 rounded-2xl text-center">
                        <span class="text-[10px] text-stone-400 uppercase block mb-1">Orders</span>
                        <span id="context-user-orders" class="font-bold text-primary">0</span>
                    </div>
                </div>

                <div class="mb-8">
                    <h5 class="text-xs font-bold mb-3 flex items-center gap-2">
                        <span class="material-symbols-outlined text-sm text-tertiary" data-icon="biotech">biotech</span>
                        Skin Analysis Profile
                    </h5>
                    <div id="context-skin-tags" class="flex flex-wrap gap-2">
                        <span class="text-xs text-stone-400 italic">No skin scans run yet.</span>
                    </div>
                </div>
                
                <!-- <button class="w-full mt-auto py-3 border border-outline-variant/30 text-xs font-bold rounded-xl hover:bg-stone-50 transition-colors">
                    View Full Customer Profile
                </button> -->
            </section>
        </div>
    </main>

    <script>
        let activeRoomId = null;
        let currentActiveUserId = null;
        let pusherClient = null;
        let activePusherChannelName = null;
        
        // State tracking configuration logic variable
        let currentTabStatus = 'open'; 

        window.onload = function() {
            console.log("SkinMate Admin Frame Verified: Building socket bindings...");
            
            try {
                if (typeof Pusher !== 'undefined') {
                    pusherClient = new Pusher('1b9d74bdae41a56142c8', { cluster: 'ap1' });
                    console.log("Pusher Engine: Connected");
                } else {
                    console.warn("Pusher CDN script missing or blocked.");
                }
            } catch (e) {
                console.error("Pusher Boot Exception:", e);
            }

            setupNativeEmojiPicker();
            
            refreshTicketQueueList();
            
            // Background syncing loop stays quiet
            setInterval(() => {
                refreshTicketQueueList();
            }, 10000);
        };

        function setupNativeEmojiPicker() {
            const triggerButton = document.getElementById('emoji-trigger-btn');
            const inputArea = document.getElementById('admin-reply-input');
            
            if (!triggerButton || !inputArea) return;

            // 1. Programmatically assemble a styled container dropdown card
            const emojiPanel = document.createElement('div');
            emojiPanel.id = 'native-emoji-dropdown';
            // Tailwind design architecture alignment specs matching your design aesthetic
            emojiPanel.className = 'hidden absolute bottom-12 right-0 z-50 bg-white border border-stone-200 p-3 rounded-2xl shadow-xl w-64 grid grid-cols-6 gap-2 max-h-48 overflow-y-auto select-none';
            
            // Populating a curated roster of universally supported modern emojis
            const targetEmojis = [
                '😀','😁','😂','🤣','😃','😄','😅','😆','😉','😊','😋','😎','😍','😘',
                '😗','😙','😚','🙂','🤗','🤩','🤔','🤨','😐','😑','😶','🙄','😏','😣',
                '😥','😮','🤐','😯','😪','😫','😴','😌','😛','😜','😝','🤤','😒','😓',
                '😔','😕','🙃','🤑','😲','☹️','🙁','😖','😞','😟','😤','😢','😭','😦',
                '💥','💯','👋','👍','👎','👏','🙌','🙏','❤️','✨','🚀','💡','✅','❌'
            ];

            targetEmojis.forEach(emoji => {
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'hover:bg-stone-100 p-1 rounded-lg text-lg transition-all transform hover:scale-110 active:scale-95 text-center';
                btn.innerText = emoji;
                
                btn.addEventListener('click', (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    
                    // Smart text cursor insertion
                    const startPos = inputArea.selectionStart;
                    const endPos = inputArea.selectionEnd;
                    const currentText = inputArea.value;

                    inputArea.value = currentText.substring(0, startPos) + emoji + currentText.substring(endPos);
                    inputArea.focus();
                    inputArea.selectionStart = inputArea.selectionEnd = startPos + emoji.length;
                });
                emojiPanel.appendChild(btn);
            });

            // Anchor the panel box element into the button node's relative flex layout parent
            triggerButton.parentElement.appendChild(emojiPanel);

            // Toggle visibility on button interaction
            triggerButton.addEventListener('click', (e) => {
                e.stopPropagation();
                emojiPanel.classList.toggle('hidden');
            });

            // Dismiss the dropdown immediately if clicking anywhere else outside the panel scope
            document.addEventListener('click', (e) => {
                if (!emojiPanel.contains(e.target) && e.target !== triggerButton) {
                    emojiPanel.classList.add('hidden');
                }
            });
        }

        // Tab selection swapping function
        function switchQueueTab(status) {
            currentTabStatus = status;
            
            const pendingTab = document.getElementById('tab-pending');
            const historyTab = document.getElementById('tab-history');
            const titleLabel = document.getElementById('queue-title-label');

            if (status === 'open') {
                if (titleLabel) titleLabel.innerText = "Active Tickets";
                if (pendingTab) pendingTab.className = "bg-primary text-on-primary px-3 py-1 rounded-full text-xs whitespace-nowrap cursor-pointer transition-all";
                if (historyTab) historyTab.className = "bg-surface-container-lowest text-on-surface-variant border border-outline-variant/20 px-3 py-1 rounded-full text-xs whitespace-nowrap cursor-pointer hover:bg-white transition-all";
            } else {
                if (titleLabel) titleLabel.innerText = "Ticket History";
                if (historyTab) historyTab.className = "bg-primary text-on-primary px-3 py-1 rounded-full text-xs whitespace-nowrap cursor-pointer transition-all";
                if (pendingTab) pendingTab.className = "bg-surface-container-lowest text-on-surface-variant border border-outline-variant/20 px-3 py-1 rounded-full text-xs whitespace-nowrap cursor-pointer hover:bg-white transition-all";
            }

            const queueContainer = document.getElementById('admin-ticket-queue');
            if (queueContainer) queueContainer.innerHTML = '<p class="text-xs text-stone-400 text-center mt-4">Filtering ticket parameters...</p>';

            refreshTicketQueueList();
        }

        function refreshTicketQueueList() {
            const queueContainer = document.getElementById('admin-ticket-queue');
            const counterLabel = document.getElementById('label-queue-count');

            if (!queueContainer) return;

            const cacheBuster = new Date().getTime();

            fetch(`backend/get_rooms.php?status=${currentTabStatus}&_cb=${cacheBuster}`)
                .then(res => {
                    if (!res.ok) throw new Error(`HTTP System Error ${res.status}`);
                    return res.text();
                })
                .then(text => {
                    try {
                        const data = JSON.parse(text);
                        
                        if (data.status !== 'success') {
                            queueContainer.innerHTML = `<p class="text-xs text-red-400 text-center mt-4">Backend Fault: ${data.message || 'Unknown'}</p>`;
                            return;
                        }
                        
                        if (counterLabel) {
                            counterLabel.innerText = data.rooms ? data.rooms.length : 0;
                        }

                        if (!data.rooms || data.rooms.length === 0) {
                            const emptyMessage = currentTabStatus === 'open' 
                                ? 'No active support tickets found.' 
                                : 'No archived chat history found.';
                            queueContainer.innerHTML = `<p class="text-xs text-stone-400 text-center mt-4">${emptyMessage}</p>`;
                            return;
                        }

                        window.loadedRoomsCache = data.rooms;
                        queueContainer.innerHTML = '';

                        data.rooms.forEach((room, index) => {
                            const isSelected = room.room_id === activeRoomId;

                            const isUnread = room.has_unread > 0 && currentTabStatus === 'open';

                            const accentBorderColor = currentTabStatus === 'open' ? 'border-primary' : 'border-stone-400';
                            const dotIndicatorColor = currentTabStatus === 'open' ? 'bg-green-500 animate-pulse' : 'bg-stone-400';
                            
                            // const stylingClasses = isSelected 
                            //     ? `p-4 bg-surface-container-lowest rounded-2xl shadow-sm border-l-4 ${accentBorderColor} cursor-pointer ticket-card-item` 
                            //     : 'p-4 hover:bg-surface-container rounded-2xl transition-all cursor-pointer group ticket-card-item';

                            // let badgeElement = room.status === 'open'
                            //     ? `<span class="ml-auto flex items-center gap-1 text-[10px] bg-tertiary-container text-on-tertiary-container px-2 py-0.5 rounded-full"><span class="material-symbols-outlined text-[10px]"></span>Pending</span>`
                            //     : `<span class="ml-auto text-[10px] bg-stone-200 text-stone-700 px-2 py-0.5 rounded-full">Closed</span>`;

                            let stylingClasses = "";
                            if (isSelected) {
                                stylingClasses = `p-4 bg-surface-container-lowest rounded-2xl shadow-sm border-l-4 ${accentBorderColor} cursor-pointer ticket-card-item`;
                            } else if (isUnread) {
                                stylingClasses = `p-4 bg-white border border-stone-200/80 rounded-2xl shadow-sm border-l-4 border-primary/50 cursor-pointer ticket-card-item hover:bg-stone-50/50 transition-all`;
                            } else {
                                stylingClasses = 'p-4 hover:bg-surface-container rounded-2xl transition-all cursor-pointer group ticket-card-item';
                            }

                            const nameFontWeight = isUnread ? 'font-black text-stone-950' : 'font-bold text-on-surface';
                            const msgFontWeight = isUnread ? 'font-bold text-stone-900' : 'text-on-surface-variant';

                            let badgeElement = "";
                            if (isUnread) {
                                badgeElement = `<span class="ml-auto flex items-center gap-1.5 text-[10px] bg-green-500/10 text-green-600 px-2.5 py-0.5 rounded-full font-black uppercase tracking-wider animate-pulse">New Msg</span>`;
                            } else {
                                badgeElement = room.status === 'open'
                                    ? `<span class="ml-auto flex items-center gap-1 text-[10px] bg-tertiary-container text-on-tertiary-container px-2 py-0.5 rounded-full">Pending</span>`
                                    : `<span class="ml-auto text-[10px] bg-stone-200 text-stone-700 px-2 py-0.5 rounded-full">Closed</span>`;
                            }

                            // Added explicit data-room-id attributes to locate individual cards seamlessly later
                            queueContainer.innerHTML += `
                                <div class="${stylingClasses}" data-room-id="${room.room_id}" onclick="selectCachedRoomByIndex(${index})">
                                    <div class="flex justify-between items-start mb-1">
                                        <span class="text-xs font-bold text-primary uppercase tracking-tighter">#TK-${room.room_id}</span>
                                        <span class="text-[10px] text-stone-400 capitalize">${room.status}</span>
                                    </div>
                                    <h3 class="font-bold text-sm mb-1 truncate ${nameFontWeight}">${room.user_name}</h3>
                                    <p class="text-xs text-on-surface-variant line-clamp-1 mb-3 ${msgFontWeight}">${room.last_message || 'No messages yet'}</p>
                                    <div class="flex items-center gap-2">
                                        <div class="w-2 h-2 rounded-full ${dotIndicatorColor}"></div>
                                        <span class="text-[11px] font-medium text-stone-500">${currentTabStatus === 'open' ? 'Live Support' : 'Past Session'}</span>
                                        ${badgeElement}
                                    </div>
                                </div>`;
                        });
                    } catch (jsonError) {
                        console.error("Format execution error: ", text);
                        queueContainer.innerHTML = `<p class="text-xs text-amber-500 text-center mt-4">Database Format Error.</p>`;
                    }
                })
                .catch(err => {
                    console.error("Sidebar Fetch Failure Log:", err);
                    queueContainer.innerHTML = `<p class="text-xs text-red-400 text-center mt-4">Connection Failed.</p>`;
                });
        }

        function selectCachedRoomByIndex(index) {
            if (window.loadedRoomsCache && window.loadedRoomsCache[index]) {
                const room = window.loadedRoomsCache[index];
                selectActiveConversationRoom(room.room_id, room.user_id, room.user_name);
            }
        }

        function selectActiveConversationRoom(roomId, userId, userName) {
            activeRoomId = roomId;
            currentActiveUserId = userId;
            
            const accentBorderColor = currentTabStatus === 'open' ? 'border-primary' : 'border-stone-400';
            document.querySelectorAll('.ticket-card-item').forEach(card => {
                const cardRoomId = parseInt(card.getAttribute('data-room-id'));
                if (cardRoomId === roomId) {
                    card.className = `p-4 bg-surface-container-lowest rounded-2xl shadow-sm border-l-4 ${accentBorderColor} cursor-pointer ticket-card-item`;
                } else {
                    // card.className = 'p-4 hover:bg-surface-container rounded-2xl transition-all cursor-pointer group ticket-card-item';
                    const cachedRoom = window.loadedRoomsCache ? window.loadedRoomsCache.find(r => r.room_id === cardRoomId) : null;
                    const isCardUnread = cachedRoom && cachedRoom.has_unread > 0 && currentTabStatus === 'open';

                    if (isCardUnread) {
                        card.className = `p-4 bg-white border border-stone-200/80 rounded-2xl shadow-sm border-l-4 border-primary/50 cursor-pointer ticket-card-item hover:bg-stone-50/50 transition-all`;
                    } else {
                        card.className = 'p-4 hover:bg-surface-container rounded-2xl transition-all cursor-pointer group ticket-card-item';
                    }
                }
            });

            const finalName = userName || `Client #${userId}`;
            const firstInitial = finalName.trim().charAt(0).toUpperCase();
            
            const avatarCircle = document.getElementById('header-user-avatar-placeholder');
            const contextAvatar = document.getElementById('context-user-avatar');
            if (avatarCircle) {
                avatarCircle.innerText = firstInitial;
            }
            if (contextAvatar) {
                contextAvatar.innerText = firstInitial;
            }

            const liveStatusDot = document.getElementById('header-user-status-dot');
            if (liveStatusDot) {
                if (currentTabStatus === 'open') {
                    liveStatusDot.className = "absolute -bottom-1 -right-1 w-4 h-4 bg-green-500 border-2 border-white rounded-full visual-block";
                } else {
                    liveStatusDot.className = "hidden";
                }
            }

            const currentRoomData = window.loadedRoomsCache ? window.loadedRoomsCache.find(r => r.room_id === roomId) : null;
            const isRoomClosed = currentRoomData && currentRoomData.status === 'closed';

            const inputArea = document.getElementById('admin-reply-input');

            if (inputArea) {
                if (isRoomClosed) {
                    inputArea.disabled = true;
                    inputArea.placeholder = "🔒 This ticket has been resolved and marked as closed.";
                    // Swaps cursor to not-allowed and dampens text colors
                    inputArea.className = "flex-1 bg-transparent border-none focus:ring-0 text-sm py-2 resize-none outline-none text-stone-400 cursor-not-allowed select-none";
                } else {
                    inputArea.disabled = false;
                    inputArea.placeholder = "Type your reply here...";
                    // Returns layout to its standard interactive look
                    inputArea.className = "flex-1 bg-transparent border-none focus:ring-0 text-sm py-2 resize-none outline-none text-on-surface";
                }
            }

            const emojiButton = document.getElementById('emoji-trigger-btn');

            if (emojiButton) {
                if (isRoomClosed) {
                    emojiButton.disabled = true;
                    // Fades out color, turns off hover animations, and updates cursor indicator
                    emojiButton.className = "p-2 text-stone-200 cursor-not-allowed pointer-events-none transition-colors focus:outline-none";
                    
                    // Safety: If your native emoji dropdown panel is open, make sure it hides immediately
                    const existingDropdown = document.getElementById('native-emoji-dropdown');
                    if (existingDropdown) existingDropdown.classList.add('hidden');
                } else {
                    emojiButton.disabled = false;
                    // Restores active hover configurations smoothly
                    emojiButton.className = "p-2 text-stone-400 hover:text-primary transition-colors focus:outline-none";
                }
            }

            const resolveBtn = document.getElementById('btn-resolve-ticket');

            if (resolveBtn) {
                if (isRoomClosed) {
                    resolveBtn.disabled = true;
                    resolveBtn.className = "px-4 py-2 bg-stone-100 rounded-full text-xs font-bold text-stone-400 cursor-not-allowed select-none";
                } else {
                    resolveBtn.disabled = false;
                    resolveBtn.className = "px-4 py-2 bg-stone-100 rounded-full text-xs font-bold text-stone-600 hover:bg-stone-200 transition-colors";
                }
            }

            document.getElementById('header-user-name').innerText = finalName;
            document.getElementById('header-room-id-label').innerText = `Room #TK-${roomId} connected`;
            document.getElementById('context-user-name').innerText = finalName;

            fetch(`backend/get_user_context.php?user_id=${userId}`)
            .then(res => res.json())
            .then(context => {
                if (context.status === 'success') {
                    // 1. Update Join Meta Strings & Order Counts
                    document.getElementById('context-user-joined').innerText = context.joined;
                    document.getElementById('context-user-lifetime').innerText = `${context.lifetime_value}`;
                    document.getElementById('context-user-orders').innerText = context.total_orders;

                    // 2. Render Skin Analytics Tag Arrays
                    const tagBox = document.getElementById('context-skin-tags');
                    tagBox.innerHTML = ''; // Reset container view

                    if (context.skin_profile) {
                        // Main Classification Tag
                        tagBox.innerHTML += `<span class="bg-tertiary-container text-on-tertiary-container text-[11px] font-bold px-3 py-1.5 rounded-full uppercase">${context.skin_profile.skin_type}</span>`;
                        
                        // Main Overall Health Score Index
                        tagBox.innerHTML += `<span class="bg-primary-container text-on-primary-container text-[11px] font-bold px-3 py-1.5 rounded-full">Score: ${context.skin_profile.health_score}%</span>`;

                        // Loop out individual problem parameter distributions matching your database layout keys
                        Object.entries(context.skin_profile.metrics).forEach(([metricName, value]) => {
                            if (value > 20) { // Only highlight as a tag badge alert if threshold issue metric exceeds 20%
                                tagBox.innerHTML += `<span class="bg-stone-100 text-stone-700 border border-stone-200 text-[10px] font-medium px-2 py-1 rounded-md">${metricName} ${value}%</span>`;
                            }
                        });
                    } else {
                        tagBox.innerHTML = '<span class="text-xs text-stone-400 italic">No skin profile reports cataloged.</span>';
                    }
                }
            })
            .catch(err => console.error("Error drawing user panel profile context context:", err));

            if (activePusherChannelName) {
                pusherClient && pusherClient.unsubscribe('chat-room-' + activePusherChannelName);
            }
            activePusherChannelName = roomId;

            if (pusherClient) {
                let channel = pusherClient.subscribe('chat-room-' + roomId);
                channel.bind('new-message', function(payload) {
                    let parsed = typeof payload === 'string' ? JSON.parse(payload) : payload;
                    appendMessageBubbleToCanvas(parsed);
                });
            }

            const canvas = document.getElementById('admin-chat-canvas');
            canvas.innerHTML = '<p class="text-xs text-stone-400 text-center mt-10">Fetching conversation logs...</p>';

            fetch(`backend/get_messages.php?room_id=${roomId}`)
                .then(res => res.json())
                .then(data => {
                    if (data.status === 'success') {
                        canvas.innerHTML = '';
                        if(data.messages && data.messages.length > 0) {
                            data.messages.forEach(msg => appendMessageBubbleToCanvas(msg));
                            setTimeout(() => {
                                canvas.scrollTop = canvas.scrollHeight;
                            }, 50);
                            
                            refreshTicketQueueList();
                        } else {
                            canvas.innerHTML = '<p class="text-xs text-stone-400 text-center mt-10">No messages found in this ticket yet.</p>';
                        }
                    }
                }).catch(err => {
                    canvas.innerHTML = '<p class="text-xs text-red-400 text-center mt-10">Failed to load message history.</p>';
                });
        }

        function appendMessageBubbleToCanvas(msg) {
            const canvas = document.getElementById('admin-chat-canvas');
            if(canvas.querySelector('.text-center')) canvas.innerHTML = '';

            if (msg.sender_type === 'user') {
                canvas.innerHTML += `
                    <div class="flex gap-4 max-w-2xl">
                        <div class="flex-1">
                            <div class="inline-block bg-surface-container-low p-4 rounded-2xl rounded-tl-none text-sm leading-relaxed text-on-surface">
                                ${msg.message}
                            </div>
                            <span class="text-[10px] text-stone-400 mt-2 block">${msg.time || 'Just now'}</span>
                        </div>
                    </div>`;
            } else {
                canvas.innerHTML += `
                    <div class="flex gap-4 max-w-2xl ml-auto flex-row-reverse">
                        <div class="flex-1 text-right">
                            <div class="inline-block bg-secondary-container text-on-secondary-container p-4 rounded-2xl rounded-tr-none text-sm text-left">
                                ${msg.message}
                            </div>
                            <span class="text-[10px] text-stone-400 mt-2 block">${msg.time || 'Just now'}</span>
                        </div>
                    </div>`;
            }
            canvas.scrollTop = canvas.scrollHeight;
        }

        function sendAdminMessageText() {
            const inputArea = document.getElementById('admin-reply-input');
            const typedText = inputArea.value.trim();

            if (!typedText || !activeRoomId) return;
            inputArea.value = '';

            fetch('backend/admin_send.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ room_id: activeRoomId, message: typedText })
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    refreshTicketQueueList();
                } else {
                    alert("Error sending message: " + data.message);
                }
            }).catch(err => console.error("POST error:", err));
        }

        function resolveCurrentActiveTicket() {
            // Guard clause checking if a room is even selected
            if (!activeRoomId) {
                alert("Please select an active ticket queue frame to resolve first.");
                return;
            }

            if (!confirm(`Are you sure you want to mark Ticket #TK-${activeRoomId} as fully resolved?`)) {
                return;
            }

            // Target the button context element to avoid multiple click loops
            const resolveBtn = document.getElementById('btn-resolve-ticket');
            if (resolveBtn) {
                resolveBtn.disabled = true;
                resolveBtn.innerText = "Closing...";
            }

            fetch('backend/close_room.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ room_id: activeRoomId })
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    console.log(`Ticket #TK-${activeRoomId} cataloged successfully as closed.`);
                    
                    // 1. Wipe out center active canvas data frames
                    resetAdminWorkspaceCanvas();

                    // 2. Forces left sidebar queue UI loops to instantly flash modifications
                    refreshTicketQueueList();
                } else {
                    alert("System Fault: " + data.message);
                }
            })
            .catch(err => console.error("Critical Resolution Request Interruption Failure:", err))
            .finally(() => {
                // Re-enable interactive button attributes
                if (resolveBtn) {
                    resolveBtn.disabled = false;
                    resolveBtn.innerText = "Resolve Ticket";
                }
            });
        }

        function resetAdminWorkspaceCanvas() {
            activeRoomId = null;
            currentActiveUserId = null;

            // Clear header indicators
            document.getElementById('header-user-name').innerText = "Select a Room";
            document.getElementById('header-room-id-label').innerText = "Waiting for connection...";
            document.getElementById('context-user-name').innerText = "-";
            document.getElementById('context-user-joined').innerText = "Gathering details...";
            document.getElementById('context-user-lifetime').innerText = "RM 0.00";
            document.getElementById('context-user-orders').innerText = "0";
            document.getElementById('context-skin-tags').innerHTML = '<span class="text-xs text-stone-400 italic">No skin scans run yet.</span>';

            // Clear avatar circular layouts
            const avatarCircle = document.getElementById('header-user-avatar-placeholder');
            if (avatarCircle) avatarCircle.innerText = "-";
            const contextAvatar = document.getElementById('context-user-avatar');
            if (contextAvatar) contextAvatar.innerText = "-";

            // Hide the status ring completely
            const liveStatusDot = document.getElementById('header-user-status-dot');
            if (liveStatusDot) liveStatusDot.className = "hidden";

            // Disconnect standard socket channels gracefully if active
            if (activePusherChannelName && pusherClient) {
                pusherClient.unsubscribe('chat-room-' + activePusherChannelName);
                activePusherChannelName = null;
            }

            // Fill canvas messaging canvas wrapper text nodes safely
            const canvas = document.getElementById('admin-chat-canvas');
            if (canvas) {
                canvas.innerHTML = `
                    <div class="flex flex-col items-center justify-center h-full text-center text-stone-400">
                        <span class="material-symbols-outlined text-4xl mb-2" data-icon="forum">forum</span>
                        <p class="text-sm font-semibold">No ongoing chat selected</p>
                        <p class="text-xs max-w-xs mt-1">Please select an active ticket entry row from the left panel sidebar column queue to view messages.</p>
                    </div>`;
            }
        }
    </script>
</body>
</html>