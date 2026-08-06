// Wrap this script in an isolated scope to avoid leaking globals.
(function () {
    // Small local emoji set keeps the picker dependency-free and consistent on
    // guest, customer, and admin support pages.
    const emojis = ['😀', '🙂', '😊', '🙏', '👍', '👌', '✅', '📦', '🍽️', '💳', '🚚', '❤️'];

    // Helper callback for the insertAtCursor behavior in this script.
    const insertAtCursor = (input, value) => {
        // Preserve the user's cursor position so emoji insertion feels like a
        // normal chat app instead of always appending to the end.
        const start = input.selectionStart ?? input.value.length;
        const end = input.selectionEnd ?? input.value.length;
        input.value = input.value.slice(0, start) + value + input.value.slice(end);
        input.focus();
        input.selectionStart = start + value.length;
        input.selectionEnd = start + value.length;
    };

    // Helper callback for the attachmentKind behavior in this script.
    const attachmentKind = (file) => {
        // The preview renderer branches by broad MIME group; unsupported types
        // still upload as downloadable files.
        if (file.type.startsWith('image/')) {
            return 'image';
        }

        // Run this branch only when the required UI state is present.
        if (file.type.startsWith('video/')) {
            return 'video';
        }

        return 'file';
    };

    const supportRoots = document.querySelectorAll('[data-support-page], .af-admin-support-page');

    // Run this branch only when the required UI state is present.
    if (!supportRoots.length) {
        return;
    }

    let audioContext = null;

    // Helper callback for the tone behavior in this script.
    const tone = (frequency, duration = 0.08) => {
        // Protect optional browser behavior from stopping the page script.
        try {
            // Fallback tone for browsers or installs where custom audio files
            // are unavailable.
            const AudioCtor = window.AudioContext || window.webkitAudioContext;
            // Run this branch only when the required UI state is present.
            if (!AudioCtor) {
                return;
            }

            audioContext = audioContext || new AudioCtor();
            const oscillator = audioContext.createOscillator();
            const gain = audioContext.createGain();
            oscillator.type = 'sine';
            oscillator.frequency.value = frequency;
            gain.gain.setValueAtTime(0.0001, audioContext.currentTime);
            gain.gain.exponentialRampToValueAtTime(0.12, audioContext.currentTime + 0.01);
            gain.gain.exponentialRampToValueAtTime(0.0001, audioContext.currentTime + duration);
            oscillator.connect(gain);
            gain.connect(audioContext.destination);
            oscillator.start();
            oscillator.stop(audioContext.currentTime + duration + 0.02);
        } catch (error) {
            // Browsers can block audio until the user interacts with the page.
        }
    };

    // Helper callback for the playSound behavior in this script.
    const playSound = (url, fallback) => {
        // Run this branch only when the required UI state is present.
        if (!url) {
            fallback();
            return;
        }

        // Prefer project-owned audio assets; fall back to generated tones if
        // autoplay policy or a missing file prevents playback.
        const audio = new Audio(url);
        audio.currentTime = 0;
        audio.volume = 0.75;
        const playPromise = audio.play();

        // Run this branch only when the required UI state is present.
        if (playPromise && typeof playPromise.catch === 'function') {
            playPromise.catch(fallback);
        }
    };

    // Helper callback for the sentSound behavior in this script.
    const sentSound = (page) => {
        playSound(page.getAttribute('data-support-sent-sound-url') || '', () => tone(620, 0.07));
    };

    // Helper callback for the receivedSound behavior in this script.
    const receivedSound = (page) => {
        playSound(page.getAttribute('data-support-received-sound-url') || '', () => {
            tone(880, 0.07);
            window.setTimeout(() => tone(660, 0.08), 90);
        });
    };

    // Helper callback for the activeInput behavior in this script.
    const activeInput = (root) => {
        const focused = document.activeElement;

        // Run this branch only when the required UI state is present.
        if (focused instanceof HTMLInputElement && focused.name === 'message' && root.contains(focused)) {
            return focused;
        }

        return root.querySelector('.af-support-composer input[name="message"]');
    };

    // Helper callback for the messageIds behavior in this script.
    const messageIds = (root, selector = '.af-chat-message[data-message-id]') => {
        return Array.from(root.querySelectorAll(selector))
            .map((item) => Number.parseInt(item.getAttribute('data-message-id') || '0', 10))
            .filter((id) => Number.isFinite(id) && id > 0);
    };

    const lastMessageId = (root) => Math.max(0, ...messageIds(root));

    // Helper callback for the lastIncomingMessageId behavior in this script.
    const lastIncomingMessageId = (root, context) => {
        const ownTypes = context === 'admin' ? ['agent'] : ['customer', 'guest'];
        return Math.max(0, ...Array.from(root.querySelectorAll('.af-chat-message[data-message-id]'))
            .filter((item) => !ownTypes.includes(item.getAttribute('data-sender-type') || ''))
            .map((item) => Number.parseInt(item.getAttribute('data-message-id') || '0', 10))
            .filter((id) => Number.isFinite(id) && id > 0));
    };

    // Helper callback for the liveUrl behavior in this script.
    const liveUrl = (page, action, conversationId) => {
        // Build poll/send URLs from data attributes so the same JS can drive
        // admin, customer, and guest support pages.
        const url = new URL(page.getAttribute('data-support-live-url') || '', window.location.origin);
        url.searchParams.set('action', action);
        url.searchParams.set('context', page.getAttribute('data-support-context') || '');
        // Run this branch only when the required UI state is present.
        if (conversationId > 0) {
            url.searchParams.set('conversation_id', String(conversationId));
        }
        return url;
    };

    supportRoots.forEach((page) => {
        const chatBody = page.querySelector('.af-chat-body');
        const context = page.getAttribute('data-support-context') || '';
        let conversationId = Number.parseInt(page.getAttribute('data-support-conversation-id') || '0', 10);
        let currentLastMessageId = lastMessageId(page);
        let currentLastIncomingId = lastIncomingMessageId(page, context);
        let isSending = false;

        // Run this branch only when the required UI state is present.
        if (chatBody) {
            chatBody.scrollTop = chatBody.scrollHeight;
        }

        page.querySelectorAll('[data-support-topic]').forEach((button) => {
            // Bind the UI event handler for this interactive control.
            button.addEventListener('click', () => {
                // Quick help topics prefill the composer but do not send
                // automatically; the user can edit before submitting.
                const input = activeInput(page);

                // Run this branch only when the required UI state is present.
                if (!input) {
                    return;
                }

                input.value = button.getAttribute('data-support-topic') || '';
                input.focus();
            });
        });

        page.querySelectorAll('.af-support-composer').forEach((composer) => {
            const input = composer.querySelector('input[name="message"]');
            const emojiButton = composer.querySelector('[data-support-emoji-toggle]');
            const attachButton = composer.querySelector('[data-support-attach-toggle]');
            const fileInput = composer.querySelector('[data-support-file-input]');
            const fileName = composer.querySelector('[data-support-attachment-name]');
            const submitButton = composer.querySelector('button[type="submit"]');
            let previewUrl = '';
            const preview = document.createElement('div');
            preview.className = 'af-attachment-preview';
            preview.hidden = true;
            composer.appendChild(preview);

            // Helper callback for the clearPreview behavior in this script.
            const clearPreview = () => {
                // Revoke object URLs whenever a selected file is removed to
                // avoid leaking browser memory during long chat sessions.
                if (previewUrl) {
                    URL.revokeObjectURL(previewUrl);
                    previewUrl = '';
                }

                preview.hidden = true;
                preview.replaceChildren();

                // Run this branch only when the required UI state is present.
                if (fileName) {
                    fileName.textContent = '';
                    fileName.title = '';
                }
            };

            // Run this branch only when the required UI state is present.
            if (emojiButton && input) {
                const picker = document.createElement('div');
                picker.className = 'af-emoji-picker';
                picker.hidden = true;
                emojis.forEach((emoji) => {
                    const button = document.createElement('button');
                    button.type = 'button';
                    button.textContent = emoji;
                    button.setAttribute('aria-label', `Insert ${emoji}`);
                    // Bind the UI event handler for this interactive control.
                    button.addEventListener('click', () => {
                        insertAtCursor(input, emoji);
                        picker.hidden = true;
                    });
                    picker.appendChild(button);
                });
                composer.appendChild(picker);

                // Bind the UI event handler for this interactive control.
                emojiButton.addEventListener('click', (event) => {
                    event.preventDefault();
                    picker.hidden = !picker.hidden;
                    input.focus();
                });
            }

            // Run this branch only when the required UI state is present.
            if (attachButton && fileInput) {
                // Bind the UI event handler for this interactive control.
                attachButton.addEventListener('click', (event) => {
                    event.preventDefault();
                    fileInput.click();
                });

                // Bind the UI event handler for this interactive control.
                fileInput.addEventListener('change', () => {
                    const file = fileInput.files && fileInput.files[0] ? fileInput.files[0] : null;
                    clearPreview();

                    // Run this branch only when the required UI state is present.
                    if (!file) {
                        return;
                    }

                    // Run this branch only when the required UI state is present.
                    if (fileName) {
                        fileName.textContent = file.name;
                        fileName.title = file.name;
                    }

                    previewUrl = URL.createObjectURL(file);
                    const removeButton = document.createElement('button');
                    removeButton.type = 'button';
                    removeButton.setAttribute('aria-label', 'Remove attachment');
                    removeButton.innerHTML = '<i class="bi bi-x-lg" aria-hidden="true"></i>';
                    // Bind the UI event handler for this interactive control.
                    removeButton.addEventListener('click', () => {
                        fileInput.value = '';
                        clearPreview();
                    });

                    // Run this branch only when the required UI state is present.
                    if (attachmentKind(file) === 'image') {
                        const image = document.createElement('img');
                        image.src = previewUrl;
                        image.alt = file.name;
                        preview.append(image);
                    } else if (attachmentKind(file) === 'video') {
                        const video = document.createElement('video');
                        video.src = previewUrl;
                        video.controls = true;
                        video.preload = 'metadata';
                        preview.append(video);
                    } else {
                        const fileChip = document.createElement('span');
                        fileChip.innerHTML = '<i class="bi bi-paperclip" aria-hidden="true"></i>';
                        fileChip.append(document.createTextNode(file.name));
                        preview.append(fileChip);
                    }

                    preview.append(removeButton);
                    preview.hidden = false;
                });
            }

            // Bind the UI event handler for this interactive control.
            composer.addEventListener('submit', async (event) => {
                const endpoint = page.getAttribute('data-support-live-url') || '';

                // Run this branch only when the required UI state is present.
                if (!endpoint || isSending) {
                    return;
                }

                event.preventDefault();
                isSending = true;

                // Run this branch only when the required UI state is present.
                if (submitButton) {
                    submitButton.disabled = true;
                    submitButton.classList.add('is-sending');
                }

                const formData = new FormData(composer);
                formData.set('context', context);
                formData.set('live_action', 'send');
                // Run this branch only when the required UI state is present.
                if (conversationId > 0) {
                    formData.set('conversation_id', String(conversationId));
                }

                // Protect optional browser behavior from stopping the page script.
                try {
                    const response = await fetch(liveUrl(page, 'send', conversationId), {
                        method: 'POST',
                        body: formData,
                        headers: {'X-Requested-With': 'fetch'},
                    });
                    const data = await response.json();

                    // Run this branch only when the required UI state is present.
                    if (data.messages_html) {
                        // Run this branch only when the required UI state is present.
                        if (chatBody) {
                            chatBody.innerHTML = data.messages_html;
                            chatBody.scrollTop = chatBody.scrollHeight;
                        }
                        conversationId = Number.parseInt(data.conversation_id || conversationId || 0, 10);
                        page.setAttribute('data-support-conversation-id', String(conversationId));
                        composer.querySelectorAll('input[name="conversation_id"]').forEach((input) => {
                            input.value = String(conversationId);
                        });
                        currentLastMessageId = Number.parseInt(data.last_message_id || currentLastMessageId, 10);
                        currentLastIncomingId = Number.parseInt(data.last_incoming_message_id || currentLastIncomingId, 10);
                    }

                    // Run this branch only when the required UI state is present.
                    if (!data.success) {
                        window.alert(data.message || 'Support message could not be sent.');
                        return;
                    }

                    // Run this branch only when the required UI state is present.
                    if (input) {
                        input.value = '';
                    }
                    // Run this branch only when the required UI state is present.
                    if (fileInput) {
                        fileInput.value = '';
                    }
                    clearPreview();
                    sentSound(page);
                } catch (error) {
                    window.alert('Support message could not be sent. Please try again.');
                } finally {
                    isSending = false;
                    // Run this branch only when the required UI state is present.
                    if (submitButton) {
                        submitButton.disabled = false;
                        submitButton.classList.remove('is-sending');
                    }
                }
            });
        });

        const poll = async () => {
            const endpoint = page.getAttribute('data-support-live-url') || '';

            // Run this branch only when the required UI state is present.
            if (!endpoint || isSending || document.hidden) {
                return;
            }

            // Protect optional browser behavior from stopping the page script.
            try {
                const response = await fetch(liveUrl(page, 'poll', conversationId), {
                    headers: {'X-Requested-With': 'fetch'},
                });
                const data = await response.json();

                // Run this branch only when the required UI state is present.
                if (!data.success) {
                    return;
                }

                // Run this branch only when the required UI state is present.
                if (!chatBody && context === 'admin' && Number.parseInt(data.conversation_id || '0', 10) > 0) {
                    window.location.href = `support.php?view=${Number.parseInt(data.conversation_id, 10)}`;
                    return;
                }

                const nextLastMessageId = Number.parseInt(data.last_message_id || '0', 10);
                const nextIncomingId = Number.parseInt(data.last_incoming_message_id || '0', 10);

                // Run this branch only when the required UI state is present.
                if (data.messages_html && nextLastMessageId > currentLastMessageId && chatBody) {
                    chatBody.innerHTML = data.messages_html;
                    chatBody.scrollTop = chatBody.scrollHeight;

                    // Run this branch only when the required UI state is present.
                    if (currentLastIncomingId > 0 && nextIncomingId > currentLastIncomingId) {
                        receivedSound(page);
                    }

                    currentLastMessageId = nextLastMessageId;
                    currentLastIncomingId = nextIncomingId;
                    conversationId = Number.parseInt(data.conversation_id || conversationId || 0, 10);
                    page.setAttribute('data-support-conversation-id', String(conversationId));
                }
            } catch (error) {
                // Poll again on the next interval.
            }
        };

        // Run this branch only when the required UI state is present.
        if (page.getAttribute('data-support-live-url')) {
            window.setInterval(poll, 3000);
        }

        page.querySelectorAll('[data-support-rating] button').forEach((button, index, buttons) => {
            // Bind the UI event handler for this interactive control.
            button.addEventListener('click', () => {
                buttons.forEach((item, itemIndex) => {
                    item.classList.toggle('is-active', itemIndex <= index);
                    const icon = item.querySelector('i');

                    // Run this branch only when the required UI state is present.
                    if (icon) {
                        icon.className = itemIndex <= index ? 'bi bi-star-fill' : 'bi bi-star';
                    }
                });
            });
        });
    });
})();
