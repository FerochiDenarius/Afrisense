(function () {
    const emojis = ['😀', '🙂', '😊', '🙏', '👍', '👌', '✅', '📦', '🍽️', '💳', '🚚', '❤️'];

    const insertAtCursor = (input, value) => {
        const start = input.selectionStart ?? input.value.length;
        const end = input.selectionEnd ?? input.value.length;
        input.value = input.value.slice(0, start) + value + input.value.slice(end);
        input.focus();
        input.selectionStart = start + value.length;
        input.selectionEnd = start + value.length;
    };

    const attachmentKind = (file) => {
        if (file.type.startsWith('image/')) {
            return 'image';
        }

        if (file.type.startsWith('video/')) {
            return 'video';
        }

        return 'file';
    };

    const supportRoots = document.querySelectorAll('[data-support-page], .af-admin-support-page');

    if (!supportRoots.length) {
        return;
    }

    const activeInput = (root) => {
        const focused = document.activeElement;

        if (focused instanceof HTMLInputElement && focused.name === 'message' && root.contains(focused)) {
            return focused;
        }

        return root.querySelector('.af-support-composer input[name="message"]');
    };

    supportRoots.forEach((page) => {
        const chatBody = page.querySelector('.af-chat-body');

        if (chatBody) {
            chatBody.scrollTop = chatBody.scrollHeight;
        }

        page.querySelectorAll('[data-support-topic]').forEach((button) => {
            button.addEventListener('click', () => {
                const input = activeInput(page);

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
            let previewUrl = '';
            const preview = document.createElement('div');
            preview.className = 'af-attachment-preview';
            preview.hidden = true;
            composer.appendChild(preview);

            const clearPreview = () => {
                if (previewUrl) {
                    URL.revokeObjectURL(previewUrl);
                    previewUrl = '';
                }

                preview.hidden = true;
                preview.replaceChildren();

                if (fileName) {
                    fileName.textContent = '';
                    fileName.title = '';
                }
            };

            if (emojiButton && input) {
                const picker = document.createElement('div');
                picker.className = 'af-emoji-picker';
                picker.hidden = true;
                emojis.forEach((emoji) => {
                    const button = document.createElement('button');
                    button.type = 'button';
                    button.textContent = emoji;
                    button.setAttribute('aria-label', `Insert ${emoji}`);
                    button.addEventListener('click', () => {
                        insertAtCursor(input, emoji);
                        picker.hidden = true;
                    });
                    picker.appendChild(button);
                });
                composer.appendChild(picker);

                emojiButton.addEventListener('click', (event) => {
                    event.preventDefault();
                    picker.hidden = !picker.hidden;
                    input.focus();
                });
            }

            if (attachButton && fileInput) {
                attachButton.addEventListener('click', (event) => {
                    event.preventDefault();
                    fileInput.click();
                });

                fileInput.addEventListener('change', () => {
                    const file = fileInput.files && fileInput.files[0] ? fileInput.files[0] : null;
                    clearPreview();

                    if (!file) {
                        return;
                    }

                    if (fileName) {
                        fileName.textContent = file.name;
                        fileName.title = file.name;
                    }

                    previewUrl = URL.createObjectURL(file);
                    const removeButton = document.createElement('button');
                    removeButton.type = 'button';
                    removeButton.setAttribute('aria-label', 'Remove attachment');
                    removeButton.innerHTML = '<i class="bi bi-x-lg" aria-hidden="true"></i>';
                    removeButton.addEventListener('click', () => {
                        fileInput.value = '';
                        clearPreview();
                    });

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
        });

        page.querySelectorAll('[data-support-rating] button').forEach((button, index, buttons) => {
            button.addEventListener('click', () => {
                buttons.forEach((item, itemIndex) => {
                    item.classList.toggle('is-active', itemIndex <= index);
                    const icon = item.querySelector('i');

                    if (icon) {
                        icon.className = itemIndex <= index ? 'bi bi-star-fill' : 'bi bi-star';
                    }
                });
            });
        });
    });
})();
