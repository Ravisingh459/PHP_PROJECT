/**
 * NexaWork - Main JavaScript
 */

$(document).ready(function() {

    // CSRF Token for AJAX
    $.ajaxSetup({
        headers: { 'X-CSRF-TOKEN': CSRF_TOKEN }
    });

    // Dark Mode Toggle
    $('#darkModeToggle').on('click', function() {
        const isDark = $('html').attr('data-bs-theme') === 'dark';
        const newMode = isDark ? 'light' : 'dark';
        $('html').attr('data-bs-theme', newMode);
        $(this).find('i').toggleClass('fa-moon fa-sun');

        $.post(APP_URL + '/api/settings.php', {
            action: 'toggle_dark_mode',
            csrf_token: CSRF_TOKEN
        });

        if (typeof Chart !== 'undefined' && Chart.instances) {
            const textColor = newMode === 'dark' ? '#CBD5E1' : '#64748B';
            const gridColor = newMode === 'dark' ? 'rgba(255, 255, 255, 0.1)' : 'rgba(0, 0, 0, 0.1)';

            Object.values(Chart.instances).forEach(chart => {
                if (chart.options.plugins && chart.options.plugins.legend) {
                    chart.options.plugins.legend.labels.color = textColor;
                }
                if (chart.options.scales) {
                    if (chart.options.scales.x) {
                        if (chart.options.scales.x.ticks) chart.options.scales.x.ticks.color = textColor;
                        if (chart.options.scales.x.grid) chart.options.scales.x.grid.color = gridColor;
                    }
                    if (chart.options.scales.y) {
                        if (chart.options.scales.y.ticks) chart.options.scales.y.ticks.color = textColor;
                        if (chart.options.scales.y.grid) chart.options.scales.y.grid.color = gridColor;
                    }
                }
                chart.update();
            });
        }
    });

    // Language Switch
    $('.lang-switch').on('click', function(e) {
        e.preventDefault();
        const lang = $(this).data('lang');
        $.post(APP_URL + '/api/settings.php', {
            action: 'set_language',
            language: lang,
            csrf_token: CSRF_TOKEN
        }, function() {
            location.reload();
        });
    });

    // Sidebar Toggle & Close (Mobile)
    $('#sidebarToggle').on('click', function() {
        $('#sidebar').toggleClass('show');
    });
    $('#sidebarCloseBtn').on('click', function() {
        $('#sidebar').removeClass('show');
    });

    // Auto-dismiss alerts
    setTimeout(function() {
        $('.alert-dismissible').fadeOut('slow');
    }, 5000);

    // Notification click - mark as read
    $('.notification-item').on('click', function() {
        const id = $(this).data('id');
        if (id) {
            $.post(APP_URL + '/api/notifications.php', {
                action: 'mark_read',
                id: id,
                csrf_token: CSRF_TOKEN
            });
        }
    });

    // Poll for new notifications every 30 seconds
    if ($('#notificationDropdown').length) {
        setInterval(pollNotifications, 30000);
    }

    // Star rating input
    $('.star-input i').on('click', function() {
        const rating = $(this).data('value');
        $('#ratingValue').val(rating);
        $('.star-input i').each(function() {
            $(this).toggleClass('fas far').toggleClass('text-warning',
                $(this).data('value') <= rating);
        });
    });

    // Confirm delete
    $('.btn-delete').on('click', function(e) {
        if (!confirm('Are you sure you want to delete this item?')) {
            e.preventDefault();
        }
    });

    // File upload preview
    $('input[type="file"][data-preview]').on('change', function() {
        const preview = $('#' + $(this).data('preview'));
        if (this.files && this.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                preview.attr('src', e.target.result).show();
            };
            reader.readAsDataURL(this.files[0]);
        }
    });

    // Budget range slider
    if ($('#budgetRange').length) {
        const slider = document.getElementById('budgetRange');
        const output = document.getElementById('budgetValue');
    output.textContent = '₹' + Number(slider.value).toLocaleString('en-IN');
    slider.oninput = function() {
        output.textContent = '₹' + Number(this.value).toLocaleString('en-IN');
        };
    }

    // Initialize tooltips
    const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
    tooltipTriggerList.forEach(el => new bootstrap.Tooltip(el));
});

/**
 * Poll for new notifications
 */
function pollNotifications() {
    $.get(APP_URL + '/api/notifications.php?action=count', function(data) {
        if (data.count > 0) {
            $('.notification-badge').text(data.count).show();
        } else {
            $('.notification-badge').hide();
        }
    });
}

/**
 * AJAX form submission helper
 */
function submitForm(form, callback) {
    const $form = $(form);
    const url = $form.attr('action') || window.location.href;
    const formData = new FormData(form);

    $.ajax({
        url: url,
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        beforeSend: function() {
            $form.find('[type="submit"]').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Loading...');
        },
        success: function(response) {
            if (typeof response === 'string') {
                try { response = JSON.parse(response); } catch(e) {}
            }
            if (callback) callback(response);
        },
        error: function(xhr) {
            alert('An error occurred. Please try again.');
        },
        complete: function() {
            $form.find('[type="submit"]').prop('disabled', false).html('Submit');
        }
    });
}

/**
 * Chat messaging with AJAX polling
 */
const Chat = {
    partnerId: null,
    lastMessageId: 0,
    pollInterval: null,

    init: function(partnerId, initialLastId = 0) {
        this.partnerId = partnerId;
        this.lastMessageId = initialLastId || 0;
        this.scrollToBottom();
        if (!initialLastId) {
            this.loadMessages();
        }
        this.pollInterval = setInterval(() => this.loadMessages(), 3000);
    },

    loadMessages: function() {
        if (!this.partnerId) return;
        $.get(APP_URL + '/api/messages.php', {
            action: 'get_conversation',
            partner_id: this.partnerId,
            last_id: this.lastMessageId
        }, (data) => {
            if (data.messages && data.messages.length) {
                data.messages.forEach(msg => {
                    this.appendMessage(msg);
                    this.lastMessageId = Math.max(this.lastMessageId, msg.id);
                });
                this.scrollToBottom();
            }
        });
    },

    appendMessage: function(msg) {
        const isSent = msg.sender_id == CURRENT_USER_ID;
        const html = `<div class="chat-message ${isSent ? 'sent' : 'received'}">
            <div>${msg.body}</div>
            <small class="opacity-75">${msg.time}</small>
        </div>`;
        $('#chatMessages').append(html);
    },

    send: function() {
        const body = $('#messageInput').val().trim();
        if (!body) return;

        $.post(APP_URL + '/api/messages.php', {
            action: 'send',
            receiver_id: this.partnerId,
            body: body,
            csrf_token: CSRF_TOKEN
        }, (data) => {
            if (data.success) {
                $('#messageInput').val('');
                this.loadMessages();
            }
        });
    },

    scrollToBottom: function() {
        const container = document.getElementById('chatMessages');
        if (container) container.scrollTop = container.scrollHeight;
    },

    destroy: function() {
        if (this.pollInterval) clearInterval(this.pollInterval);
    }
};

/**
 * Chart.js helper for analytics dashboards
 */
function createChart(canvasId, type, labels, datasets, options = {}) {
    const ctx = document.getElementById(canvasId);
    if (!ctx) return null;

    const isDark = $('html').attr('data-bs-theme') === 'dark' || $('body').hasClass('dark-mode');
    const textColor = isDark ? '#CBD5E1' : '#64748B';
    const gridColor = isDark ? 'rgba(255, 255, 255, 0.1)' : 'rgba(0, 0, 0, 0.08)';

    const defaultOptions = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom',
                labels: {
                    color: textColor,
                    padding: 16,
                    font: { family: 'Inter', size: 12, weight: '500' }
                }
            },
            tooltip: {
                backgroundColor: isDark ? '#1E293B' : '#FFFFFF',
                titleColor: isDark ? '#F1F5F9' : '#0F172A',
                bodyColor: isDark ? '#CBD5E1' : '#334155',
                borderColor: isDark ? '#334155' : '#E2E8F0',
                borderWidth: 1,
                padding: 10,
                boxPadding: 6
            }
        },
        scales: (type !== 'doughnut' && type !== 'pie') ? {
            x: {
                ticks: { color: textColor, font: { family: 'Inter', size: 11 } },
                grid: { color: gridColor }
            },
            y: {
                beginAtZero: true,
                ticks: { color: textColor, font: { family: 'Inter', size: 11 } },
                grid: { color: gridColor }
            }
        } : {}
    };

    return new Chart(ctx, {
        type: type,
        data: { labels, datasets },
        options: { ...defaultOptions, ...options }
    });
}

/**
 * Skill test timer
 */
function startSkillTestTimer(minutes, onComplete) {
    let seconds = minutes * 60;
    const display = document.getElementById('testTimer');

    const interval = setInterval(() => {
        const mins = Math.floor(seconds / 60);
        const secs = seconds % 60;
        display.textContent = `${mins}:${secs.toString().padStart(2, '0')}`;

        if (seconds <= 0) {
            clearInterval(interval);
            if (onComplete) onComplete();
        }
        seconds--;
    }, 1000);

    return interval;
}

/**
 * Show loading overlay
 */
function showLoading() {
    $('body').append('<div class="spinner-overlay"><div class="spinner-border text-primary" role="status"></div></div>');
}

function hideLoading() {
    $('.spinner-overlay').remove();
}

/**
 * Hero search with live autocomplete
 */
let searchDebounce = null;

function initHeroSearch() {
    const $input = $('#heroSearch');
    const $suggestions = $('#searchSuggestions');
    if (!$input.length) return;

    $input.on('input', function() {
        const q = $(this).val().trim();
        clearTimeout(searchDebounce);

        if (q.length < 2) {
            $suggestions.addClass('d-none').empty();
            return;
        }

        searchDebounce = setTimeout(() => {
            $.get(APP_URL + '/api/search.php', { q }, function(data) {
                if (!data.success) return;
                renderSearchSuggestions(data.results, $suggestions);
            });
        }, 300);
    });

    $('#heroSearchBtn').on('click', function() {
        const q = $input.val().trim();
        if (q) location.href = APP_URL + '/projects.php?q=' + encodeURIComponent(q);
    });

    $input.on('keydown', function(e) {
        if (e.key === 'Enter') {
            const q = $(this).val().trim();
            if (q) location.href = APP_URL + '/projects.php?q=' + encodeURIComponent(q);
        }
    });

    $(document).on('click', function(e) {
        if (!$(e.target).closest('.hero-search').length) {
            $suggestions.addClass('d-none');
        }
    });
}

function renderSearchSuggestions(results, $container) {
    let html = '';
    const groups = [
        { key: 'projects', label: 'Projects', icon: 'fa-folder' },
        { key: 'freelancers', label: 'Freelancers', icon: 'fa-user' },
        { key: 'skills', label: 'Skills', icon: 'fa-code' },
    ];

    groups.forEach(g => {
        const items = results[g.key] || [];
        if (!items.length) return;
        html += '<div class="suggestion-group"><div class="suggestion-label"><i class="fas ' + g.icon + ' me-1"></i>' + g.label + '</div>';
        items.forEach(item => {
            if (g.key === 'projects') {
                html += '<a href="' + item.url + '">' + item.title + ' <small class="text-muted">· ' + item.budget + '</small></a>';
            } else if (g.key === 'freelancers') {
                html += '<a href="' + item.url + '">' + item.name + ' <small class="text-muted">· ' + item.title + '</small></a>';
            } else {
                html += '<a href="' + item.url + '">' + item.name + '</a>';
            }
        });
        html += '</div>';
    });

    if (html) {
        $container.html(html).removeClass('d-none');
    } else {
        $container.addClass('d-none').empty();
    }
}

/**
 * Animated counter for hero stats
 */
function initCounterAnimation() {
    const counters = document.querySelectorAll('.stat-number[data-count]');
    if (!counters.length) return;

    const animate = (el) => {
        const target = parseInt(el.dataset.count, 10) || 0;
        const duration = 1500;
        const start = performance.now();

        const step = (now) => {
            const progress = Math.min((now - start) / duration, 1);
            const eased = 1 - Math.pow(1 - progress, 3);
            el.textContent = Math.floor(eased * target).toLocaleString() + (target > 0 ? '+' : '');
            if (progress < 1) requestAnimationFrame(step);
        };
        requestAnimationFrame(step);
    };

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                animate(entry.target);
                observer.unobserve(entry.target);
            }
        });
    }, { threshold: 0.5 });

    counters.forEach(c => observer.observe(c));
}

/**
 * Refresh live activity ticker
 */
function refreshActivityTicker() {
    const $ticker = $('#activityTicker');
    if (!$ticker.length) return;

    $.get(APP_URL + '/api/activity.php', { limit: 8 }, function(data) {
        if (!data.success || !data.activities.length) return;
        const items = data.activities.map(a =>
            '<span class="ticker-item"><i class="fas ' + a.icon + ' me-2"></i>' + a.text + ' <small class="opacity-75">· ' + a.time + '</small></span>'
        );
        $ticker.html(items.join('') + items.join(''));
    });
}

if ($('#activityTicker').length) {
    setInterval(refreshActivityTicker, 60000);
}
