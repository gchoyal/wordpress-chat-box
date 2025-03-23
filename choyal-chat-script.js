jQuery(document).ready(function ($) {
    let selectedUserId = 0;
    let guestEmail = '';
    let adminStatus = 'offline';

    // Fetch chat messages
    function fetchChats() {
        if (selectedUserId !== 0 || guestEmail !== '') {
            $.ajax({
                url: choyalChatAjax.ajax_url,
                method: 'POST',
                data: {
                    action: 'choyal_get_chats',
                    user_id: selectedUserId,
                    guest_email: guestEmail
                },
                success: function (response) {
                    if (response.success) {
                        let chatMessages = response.data.map(msg => `<p><strong>${msg.user_id !== 0 ? 'User ' + msg.user_id : msg.guest_name}:</strong> ${msg.message}</p>`).join('');
                        $('#choyal-chat-messages').html(chatMessages);
                        $('#choyal-chat-messages').scrollTop($('#choyal-chat-messages')[0].scrollHeight);
                        flashChatBox();
                    }
                }
            });
        }
    }

    // Send chat message
    $('#choyal-chat-send').click(function () {
        let message = $('#choyal-chat-input').val();
        if (message.trim() !== '') {
            $.ajax({
                url: choyalChatAjax.ajax_url,
                method: 'POST',
                data: {
                    action: 'choyal_send_chat',
                    message: message,
                    admin_id: selectedUserId === 0 ? 0 : selectedUserId,
                    guest_name: guestEmail === '' ? '' : $('#choyal-guest-name').val(),
                    guest_email: guestEmail === '' ? '' : $('#choyal-guest-email').val(),
                    guest_website: guestEmail === '' ? '' : $('#choyal-guest-website').val()
                },
                success: function (response) {
                    if (response.success) {
                        $('#choyal-chat-input').val('');
                        fetchChats();
                    }
                }
            });
        }
    });

    // Fetch chats every 5 seconds
    setInterval(fetchChats, 5000);
    fetchChats();

    // Guest form submission
    $('#choyal-guest-form').submit(function (e) {
        e.preventDefault();
        guestEmail = $('#choyal-guest-email').val();
        $('#choyal-guest-form').hide();
        $('#choyal-guest-chat').show();
        fetchChats();
        checkAdminStatus();
    });

    // Toggle chat box
    $('#choyal-chat-header').click(function () {
        $('#choyal-chat-content').toggle();
        let toggleText = $('#choyal-chat-toggle').text() === '-' ? '+' : '-';
        $('#choyal-chat-toggle').text(toggleText);
    });

    // Check admin status
    function checkAdminStatus() {
        $.ajax({
            url: choyalChatAjax.ajax_url,
            method: 'POST',
            data: {
                action: 'choyal_get_admin_status'
            },
            success: function (response) {
                if (response.success) {
                    adminStatus = response.data.status;
                    updateAdminStatus();
                }
            }
        });
    }

    function updateAdminStatus() {
        let statusDot = $('#choyal-admin-status .choyal-status-dot');
        let statusText = 'Connecting to agent...';
        if (adminStatus === 'online') {
            statusDot.removeClass('choyal-status-gray choyal-status-yellow').addClass('choyal-status-green');
            statusText = 'Agent is online';
        } else if (adminStatus === 'offline') {
            statusDot.removeClass('choyal-status-green choyal-status-yellow').addClass('choyal-status-gray');
            statusText = 'Agent is offline';
        } else {
            statusDot.removeClass('choyal-status-gray choyal-status-green').addClass('choyal-status-yellow');
            statusText = 'Agent is not active';
        }
        $('#choyal-admin-status').html(`${statusDot[0].outerHTML} ${statusText}`);
    }

    // Flash chat box
    function flashChatBox() {
        let chatBox = $('#choyal-chat-box');
        chatBox.addClass('choyal-flash');
        setTimeout(() => {
            chatBox.removeClass('choyal-flash');
        }, 1000);
    }
});