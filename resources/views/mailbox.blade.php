@extends('layouts.app')
@section('title', 'Mailbox — RepoHive')

@section('content')
<div class="mailbox">
    <aside class="sidebar">
        <div class="brand">
            <img src="{{ asset('images/computer-security.gif') }}" alt="Icon" class="brand-icon">
            <span>RepoHive</span>
        </div>

        <button class="compose-btn" id="composeBtn">+ Compose</button>

        <a class="menu active" id="inboxTab">
            Inbox
            <span>{{ $inbox->total() }}</span>
        </a>
        <a class="menu" id="sentTab">
            Sent
            <span>{{ $sent->total() }}</span>
        </a>

        <div class="sidebar-footer">
            <a href="{{ route('dashboard') }}" class="dashboard-link">← Dashboard</a>
        </div>
    </aside>

    <main class="main">
        <header class="topbar">
            <div>
                <h2 id="mailTitle">Inbox</h2>
                <small style="color:var(--muted)">{{ auth()->user()->email }}</small>
            </div>
            <input id="searchMail" placeholder="Search mail…">
        </header>

        @if(session('success'))
            <div class="alert success" style="margin:0.75rem 1.5rem">{{ session('success') }}</div>
        @endif

        @if(session('error'))
            <div class="alert error" style="margin:0.75rem 1.5rem">{{ session('error') }}</div>
        @endif

        <section class="mail-area">
            <div id="mailList" class="mail-list">

                {{-- Inbox --}}
                <div id="inboxList">
                    @forelse($inbox as $msg)
                        <div class="mail-item" data-message-id="{{ $msg->id }}" data-message-type="inbox">
                            <div class="mail-item-body">
                                <div class="from">
                                    {{ $msg->from_name ?? $msg->from_email }}
                                </div>
                                <div class="subject">
                                    {{ $msg->subject }}
                                </div>
                                <div class="date">
                                    {{ $msg->created_at->format('M j, g:i A') }}
                                </div>
                            </div>
                            <button class="delete-btn" data-id="{{ $msg->id }}">
                                Delete 🗑
                            </button>
                        </div>
                    @empty
                        <div class="empty-state">
                            <div class="empty-icon">📭</div>
                            <p>No messages in your inbox.</p>
                        </div>
                    @endforelse
                </div>

                {{-- Sent --}}
                <div id="sentList" style="display:none">
                    @forelse($sent as $msg)
                        <div class="mail-item" data-message-id="{{ $msg->id }}" data-message-type="sent">
                            <div class="mail-item-body">
                                <div class="from">To: {{ $msg->to_email }}</div>
                                <div class="subject">{{ $msg->subject }}</div>
                                <div class="date">{{ $msg->created_at->format('M j, g:i A') }}</div>
                            </div>
                            <button class="delete-btn" data-id="{{ $msg->id }}">
                                Delete 🗑
                            </button>
                        </div>
                    @empty
                        <div class="empty-state">
                            <div class="empty-icon">📤</div>
                            <p>No sent messages.</p>
                        </div>
                    @endforelse
                </div>
            </div>

            {{-- Preview Panel --}}
            <div class="preview">
                <div class="preview-placeholder" id="previewPlaceholder">
                    <div style="font-size:3rem">✉️</div>
                    <p style="color:var(--muted);font-size:1rem;margin-top:0.5rem">Select an email to read</p>
                </div>
                <div id="previewContent" style="display:none">
                    <h2 id="previewTitle" style="font-size:1.2rem;margin-bottom:0.4rem"></h2>
                    <p id="previewMeta" class="preview-meta"></p>
                    <hr style="margin:1rem 0">
                    <div id="previewBody" class="preview-body" style="white-space:pre-wrap"></div>
                </div>
            </div>
        </section>
    </main>
</div>

{{-- Compose Modal --}}
<div id="composeModal" class="modal">
    <div class="modal-card">
        <button type="button" class="close" id="closeComposeBtn">×</button>
        <h2 style="margin-bottom:0.5rem">New Message</h2>

        <form id="composeForm" action="{{ route('mailbox.send') }}" method="POST">
            @csrf
            <label>To</label>
            <input name="to" type="email" placeholder="recipient@email.com" required>

            <label>Subject</label>
            <input name="subject" type="text" placeholder="Email subject" required>

            <label>Message</label>
            <textarea name="body" placeholder="Write your message…" rows="5" required></textarea>

            <button type="submit" class="btn primary" style="margin-top:0.5rem">Send Message</button>
        </form>
    </div>
</div>

<style>
.sidebar-footer {
    margin-top: auto;
    padding-top: 1.5rem;
    border-top: 1px solid var(--border);
}

.dashboard-link {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1rem;
    border-radius: 12px;
    text-decoration: none;
    font-size: 0.95rem;
    font-weight: 600;
    color: var(--berry);
    background: var(--petal);
    border: 1px solid var(--border);
    transition: all 0.2s ease;
}

.dashboard-link:hover {
    background: var(--blush);
    border-color: var(--rose);
    transform: translateX(-2px);
}

.empty-state {
    padding: 3rem 1rem;
    text-align: center;
    color: var(--muted);
}

.empty-state .empty-icon { font-size: 2.5rem; margin-bottom: 0.75rem; }
.empty-state p { font-size: 0.95rem; }

.preview-placeholder {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    height: 100%;
    min-height: 300px;
    opacity: 0.5;
}

.mail-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1rem;
    border-bottom: 1px solid var(--border-2);
    transition: background 0.15s ease;
    cursor: pointer;
}

.mail-item:hover { background: var(--petal); }
.mail-item:hover .delete-btn { opacity: 1; }
.mail-item.active { background: var(--petal); border-left: 3px solid var(--rose); }

.mail-item-body {
    flex: 1;
    min-width: 0;
}

.mail-item-body .from {
    font-size: 0.9rem;
    font-weight: 600;
    color: var(--berry);
}

.mail-item-body .subject {
    font-size: 0.85rem;
    color: var(--muted);
    margin-top: 2px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.mail-item-body .date {
    font-size: 0.78rem;
    color: #b08a99;
    margin-top: 3px;
}

.delete-btn {
    opacity: 0;
    flex-shrink: 0;
    background: none;
    border: none;
    cursor: pointer;
    font-size: 1rem;
    padding: 0.3rem 0.4rem;
    border-radius: 8px;
    transition: all 0.15s ease;
    color: var(--muted);
}

.delete-btn:hover {
    background: #ffe9ef;
    color: #b4235a;
}

.modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
    z-index: 1000;
    align-items: center;
    justify-content: center;
}

.modal.open { display: flex; }

.modal-card {
    background: white;
    border-radius: 16px;
    padding: 2rem;
    width: 90%;
    max-width: 500px;
    position: relative;
    box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1);
    animation: modalSlideIn 0.3s ease;
}

.modal-card .close {
    position: absolute;
    top: 1rem;
    right: 1rem;
    background: none;
    border: none;
    font-size: 1.5rem;
    cursor: pointer;
    color: var(--muted);
}

.modal-card label {
    display: block;
    margin-top: 1rem;
    margin-bottom: 0.25rem;
    font-weight: 600;
    font-size: 0.875rem;
}

.modal-card input,
.modal-card textarea {
    width: 100%;
    padding: 0.75rem;
    border: 1px solid var(--border);
    border-radius: 8px;
    font-size: 0.875rem;
}

.modal-card textarea {
    resize: vertical;
    min-height: 120px;
}

.btn.primary {
    background: var(--berry);
    color: white;
    border: none;
    padding: 0.75rem 1rem;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 600;
    width: 100%;
}

.btn.primary:hover { background: var(--rose); }

@keyframes modalSlideIn {
    from { transform: scale(0.9); opacity: 0; }
    to   { transform: scale(1);   opacity: 1; }
}
</style>

<script>
(function() {
    // Wait for DOM to be ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
    
    function init() {
        console.log('Mailbox initialized');
        
        // Get elements
        var inboxList = document.getElementById('inboxList');
        var sentList = document.getElementById('sentList');
        var inboxTab = document.getElementById('inboxTab');
        var sentTab = document.getElementById('sentTab');
        var mailTitle = document.getElementById('mailTitle');
        var composeBtn = document.getElementById('composeBtn');
        var composeModal = document.getElementById('composeModal');
        var closeComposeBtn = document.getElementById('closeComposeBtn');
        var searchMail = document.getElementById('searchMail');
        
        // Tab switching
        if (inboxTab) {
            inboxTab.addEventListener('click', function(e) {
                e.preventDefault();
                showTab('inbox');
            });
        }
        
        if (sentTab) {
            sentTab.addEventListener('click', function(e) {
                e.preventDefault();
                showTab('sent');
            });
        }
        
        // Compose modal
        if (composeBtn) {
            composeBtn.addEventListener('click', function() {
                if (composeModal) composeModal.classList.add('open');
            });
        }
        
        if (closeComposeBtn) {
            closeComposeBtn.addEventListener('click', function() {
                if (composeModal) composeModal.classList.remove('open');
            });
        }
        
        // Close modal when clicking outside
        if (composeModal) {
            composeModal.addEventListener('click', function(e) {
                if (e.target === composeModal) {
                    composeModal.classList.remove('open');
                }
            });
        }
        
        // Search functionality
        if (searchMail) {
            searchMail.addEventListener('keyup', function() {
                filterMail();
            });
        }
        
        // Message click handlers - using event delegation
        document.querySelector('.mail-list').addEventListener('click', function(e) {
            // Find the closest mail-item
            var mailItem = e.target.closest('.mail-item');
            if (mailItem && !e.target.classList.contains('delete-btn')) {
                var messageId = mailItem.getAttribute('data-message-id');
                var messageType = mailItem.getAttribute('data-message-type');
                if (messageId) {
                    previewMail(messageId, messageType);
                }
            }
        });
        
        // Delete button handlers
        document.querySelector('.mail-list').addEventListener('click', function(e) {
            var deleteBtn = e.target.closest('.delete-btn');
            if (deleteBtn) {
                e.stopPropagation();
                var id = deleteBtn.getAttribute('data-id');
                if (id) {
                    deleteMessage(id);
                }
            }
        });
        
        function showTab(tab) {
            if (tab === 'inbox') {
                if (inboxList) inboxList.style.display = 'block';
                if (sentList) sentList.style.display = 'none';
                if (mailTitle) mailTitle.textContent = 'Inbox';
                
                if (inboxTab) inboxTab.classList.add('active');
                if (sentTab) sentTab.classList.remove('active');
            } else {
                if (inboxList) inboxList.style.display = 'none';
                if (sentList) sentList.style.display = 'block';
                if (mailTitle) mailTitle.textContent = 'Sent';
                
                if (inboxTab) inboxTab.classList.remove('active');
                if (sentTab) sentTab.classList.add('active');
            }
            
            // Clear preview
            var placeholder = document.getElementById('previewPlaceholder');
            var content = document.getElementById('previewContent');
            if (placeholder) placeholder.style.display = 'flex';
            if (content) content.style.display = 'none';
        }
        
        function previewMail(messageId, type) {
            console.log('Previewing message:', messageId, type);
            
            // Remove active class from all mail items
            var mailItems = document.querySelectorAll('.mail-item');
            for (var i = 0; i < mailItems.length; i++) {
                mailItems[i].classList.remove('active');
            }
            
            // Add active class to current mail item
            var currentItem = document.querySelector('.mail-item[data-message-id="' + messageId + '"]');
            if (currentItem) {
                currentItem.classList.add('active');
            }
            
            // Show preview content
            var placeholder = document.getElementById('previewPlaceholder');
            var content = document.getElementById('previewContent');
            if (placeholder) placeholder.style.display = 'none';
            if (content) content.style.display = 'block';
            
            // Set loading state
            var titleEl = document.getElementById('previewTitle');
            var metaEl = document.getElementById('previewMeta');
            var bodyEl = document.getElementById('previewBody');
            
            if (titleEl) titleEl.innerText = 'Loading...';
            if (metaEl) metaEl.innerText = '';
            if (bodyEl) bodyEl.innerText = 'Loading message content...';
            
            // Fetch the message
            fetch('/mailbox/' + messageId, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(function(response) {
                if (!response.ok) {
                    throw new Error('HTTP error ' + response.status);
                }
                return response.json();
            })
            .then(function(data) {
                console.log('Message data received:', data);
                
                if (data.success && data.data) {
                    var message = data.data;
                    if (titleEl) titleEl.innerText = message.subject || '(No subject)';
                    
                    var metaText = '';
                    if (type === 'inbox') {
                        metaText = 'From: ' + (message.from_name || message.from_email) + ' · ' + new Date(message.created_at).toLocaleString();
                    } else {
                        metaText = 'To: ' + message.to_email + ' · ' + new Date(message.created_at).toLocaleString();
                    }
                    if (metaEl) metaEl.innerText = metaText;
                    if (bodyEl) bodyEl.innerText = message.body || '(No content)';
                } else {
                    if (bodyEl) bodyEl.innerText = 'Unable to load message content.';
                }
            })
            .catch(function(error) {
                console.error('Error loading message:', error);
                var bodyEl = document.getElementById('previewBody');
                if (bodyEl) bodyEl.innerText = 'Error loading message. Please try again.';
            });
        }
        
        function filterMail() {
            var searchInput = document.getElementById('searchMail');
            if (!searchInput) return;
            
            var query = searchInput.value.toLowerCase();
            var items = document.querySelectorAll('.mail-item');
            
            for (var i = 0; i < items.length; i++) {
                var text = items[i].textContent.toLowerCase();
                items[i].style.display = text.indexOf(query) !== -1 ? '' : 'none';
            }
        }
        
        function deleteMessage(id) {
            if (!confirm('Delete this message?')) return;
            
            var token = document.querySelector('meta[name="csrf-token"]');
            if (!token) return;
            
            fetch('/mailbox/' + id, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': token.getAttribute('content'),
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                }
            })
            .then(function(res) {
                return res.json();
            })
            .then(function(data) {
                if (data.success) {
                    var item = document.querySelector('.mail-item[data-message-id="' + id + '"]');
                    if (item) item.remove();
                    
                    var placeholder = document.getElementById('previewPlaceholder');
                    var content = document.getElementById('previewContent');
                    if (placeholder) placeholder.style.display = 'flex';
                    if (content) content.style.display = 'none';
                } else {
                    alert(data.error || 'Could not delete message.');
                }
            })
            .catch(function(e) {
                console.error(e);
                alert('Network error.');
            });
        }
    }
})();
</script>
@endsection