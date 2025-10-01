document.addEventListener('DOMContentLoaded', () => {
    if (window && window.console) {
        try { console.log('Post creation modal JS loaded'); } catch (e) {}
    }
    // Post Creation Modal functionality
    
    // Initialize ACF fields in modal - WordPress standard approach
    function initializeACFFields(container) {
        if (typeof acf !== 'undefined') {
            // Use ACF's standard initialization
            acf.doAction('append', container);
        }
    }
    
    // Load create modal content
    async function loadCreateModal() {
        try {
            if (window.openModal) {
                window.openModal('<div class="loading">Loading...</div>');
            }
            
            const response = await fetch('/wp-admin/admin-ajax.php', {
                method: 'POST',
                body: new URLSearchParams({
                    action: 'get_create_modal',
                    nonce: ajax_object.nonce
                })
            });
            
            const data = await response.json();
            
            if (data.success) {
                if (window.openModal) {
                    window.openModal(data.data);
                }
            } else {
                if (window.openModal) {
                    window.openModal('<h2>Error</h2><p>Could not load create modal.</p>');
                }
            }
        } catch (error) {
            console.error('Error loading create modal:', error);
            if (window.openModal) {
                window.openModal('<h2>Error</h2><p>Could not load create modal.</p>');
            }
        }
    }
    
    // Load post creation form (with optional post_id for editing)
    async function loadPostCreationForm(postType, postId = null) {
        try {
            const params = {
                action: 'get_post_creation_form',
                post_type: postType,
                nonce: ajax_object.nonce
            };
            
            if (postId) {
                params.post_id = postId;
            }
            
            const response = await fetch('/wp-admin/admin-ajax.php', {
                method: 'POST',
                body: new URLSearchParams(params)
            });
            
            const data = await response.json();
            
            if (data.success) {
                // Update modal content with fade transition
                const modalBody = document.querySelector('#universal-modal .modal-body');
                if (modalBody) {
                    modalBody.style.opacity = '0';
                    setTimeout(() => {
                        modalBody.innerHTML = data.data;
                        modalBody.style.opacity = '1';
                        
                        // Initialize ACF fields and media functionality
                        initializeACFFields(modalBody);
                        // Initialize 1hrphoto single-uploader UI
                        init1hrphotoUploaderIfPresent(modalBody);
                        // Initialize Story featured uploader and editor if present
                        initStoryFeaturedIfPresent(modalBody);
                        initStoryEditorIfPresent(modalBody);
                    }, 200);
                }
            } else {
                showErrorMessage('Could not load form. Please try again.');
            }
        } catch (error) {
            console.error('Error loading post creation form:', error);
            showErrorMessage('Could not load form. Please try again.');
        }
    }
    
    // Submit post creation form
    async function submitPostCreationForm(formData) {
        try {
            const response = await fetch('/wp-admin/admin-ajax.php', {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            if (data.success) {
                showSuccessMessage(data.data.message);
                
                // Close modal after 2 seconds and reload current page regardless of modal timing
                setTimeout(() => { try { window.closeModal && window.closeModal(); } catch(e) {} }, 2000);
                setTimeout(() => { window.location.reload(); }, 2150);
            } else {
                if (data.data && typeof data.data === 'object') {
                    // Field-specific errors
                    displayFieldErrors(data.data);
                } else {
                    showErrorMessage('Failed to create post. Please try again.');
                }
            }
        } catch (error) {
            console.error('Error submitting form:', error);
            showErrorMessage('Failed to create post. Please try again.');
        }
    }
    
    // Display field-specific errors
    function displayFieldErrors(errors) {
        // Clear previous errors
        clearFieldErrors();
        
        // Display new errors
        Object.keys(errors).forEach(fieldName => {
            const errorElement = document.getElementById(fieldName + '-error');
            if (errorElement) {
                errorElement.textContent = errors[fieldName];
                errorElement.style.display = 'block';
            }
        });
    }
    
    // Clear all field errors
    function clearFieldErrors() {
        const errorElements = document.querySelectorAll('.field-error');
        errorElements.forEach(element => {
            element.textContent = '';
            element.style.display = 'none';
        });
    }
    
    // Show success message
    function showSuccessMessage(message) {
        const successElement = document.getElementById('success-message');
        if (successElement) {
            successElement.textContent = message;
            successElement.style.display = 'block';
        }
        
        // Hide error message if visible
        const errorElement = document.getElementById('error-message');
        if (errorElement) {
            errorElement.style.display = 'none';
        }
    }
    
    // Show error message
    function showErrorMessage(message) {
        const errorElement = document.getElementById('error-message');
        if (errorElement) {
            errorElement.textContent = message;
            errorElement.style.display = 'block';
        }
        
        // Hide success message if visible
        const successElement = document.getElementById('success-message');
        if (successElement) {
            successElement.style.display = 'none';
        }
    }
    
    // Event delegation for create option clicks
    document.body.addEventListener('click', function(e) {
        const createOption = e.target.closest('.create-option');
        if (createOption) {
            e.preventDefault();
            const postType = createOption.getAttribute('data-post-type');
            if (postType) {
                loadPostCreationForm(postType);
            }
        }
    });
    
    // Event delegation for back button (scoped to create modal)
    document.body.addEventListener('click', function(e) {
        // Global back control in header (handle clicks on inner SVG as well)
        const backBtn = e.target && (e.target.closest ? e.target.closest('.modal-back') : null);
        if (backBtn) {
            const modal = document.getElementById('universal-modal');
            if (!modal || modal.dataset.profileOpen === '1') return; // let profile modal handle its own back
            e.preventDefault();
            const body = modal.querySelector('.modal-body');
            const inForm = body && body.querySelector('.post-creation-form');
            if (inForm) { loadCreateModal(); } else { try { window.closeModal && window.closeModal(); } catch(_) {} }
            return;
        }
        // Legacy per-form back button
        if (e.target.classList.contains('btn-back')) {
            e.preventDefault();
            loadCreateModal();
        }
    });
    
    // Event delegation for form submission
    document.body.addEventListener('submit', function(e) {
        if (e.target.classList.contains('post-creation-form')) {
            e.preventDefault();
            
            const form = e.target;
            const postType = form.getAttribute('data-post-type');
            const submitButton = form.querySelector('.btn-submit');
            const btnText = submitButton.querySelector('.btn-text');
            const btnLoading = submitButton.querySelector('.btn-loading');
            const titleInput = form.querySelector('#post-title');
            const excerptInput = form.querySelector('#post-excerpt');
            const excerptError = form.querySelector('#post-excerpt-error');
            const excerptCount = form.querySelector('#excerpt-count');
            
            // Show loading state
            submitButton.disabled = true;
            btnText.style.display = 'none';
            btnLoading.style.display = 'inline';
            
            // Clear previous errors
            clearFieldErrors();
            
            // Title capitalization (client-side)
            if (titleInput && titleInput.value) {
                const normalized = titleInput.value.toLowerCase().replace(/\b\w/g, c => c.toUpperCase());
                titleInput.value = normalized;
            }

            // Excerpt character count validation (client-side UX)
            const chars = (excerptInput ? excerptInput.value : '').length;
            if (excerptCount) { excerptCount.textContent = chars + ' characters'; }
            if (chars < 100 || chars > 500) {
                if (excerptError) { excerptError.textContent = 'Excerpt must be between 100 and 500 characters.'; excerptError.style.display = 'block'; }
                submitButton.disabled = false;
                btnText.style.display = 'inline';
                btnLoading.style.display = 'none';
                return; // block submit
            }

            // For Story posts, normalize editor HTML before saving so body images
            // are stored as figure[data-attachment-id] + optional figcaption only
            if (postType === 'story') {
                const editorEl = form.querySelector('#story-editor');
                const contentHidden = form.querySelector('#post-content');
                if (editorEl && contentHidden) {
                    const clone = editorEl.cloneNode(true);
                    // Remove medium-size <img> from figures; keep data-attachment-id and captions
                    clone.querySelectorAll('figure[data-attachment-id]').forEach(fig => {
                        fig.querySelectorAll('img').forEach(img => img.remove());
                    });
                    contentHidden.value = clone.innerHTML;
                }
            }

            // Prepare form data (FormData automatically handles file uploads)
            const formData = new FormData(form);
            formData.append('action', 'create_post');
            formData.append('post_type', postType);
            formData.append('nonce', ajax_object.nonce);
            
            // Debug: Log form data contents
            console.log('Form data being sent:');
            for (let [key, value] of formData.entries()) {
                console.log(key, value);
            }
            
            // Submit form
            submitPostCreationForm(formData).finally(() => {
                // Reset button state
                submitButton.disabled = false;
                btnText.style.display = 'inline';
                btnLoading.style.display = 'none';
            });
        }
    });

    // Live excerpt character counter for UX
    document.body.addEventListener('input', function(e){
        const ta = e.target.closest('#post-excerpt');
        if (!ta) return;
        const container = ta.closest('form');
        const counter = container ? container.querySelector('#excerpt-count') : null;
        if (!counter) return;
        const chars = (ta.value || '').length;
        counter.textContent = chars + '/100 characters (max 500)';
        if (chars >= 100 && chars <= 500) {
            counter.classList.add('is-ok');
        } else {
            counter.classList.remove('is-ok');
        }
    });
    
    // Event delegation for create post shortcode clicks (styling-free)
    document.body.addEventListener('click', function(e) {
        const createPostLink = e.target.closest('[data-create-post]');
        if (createPostLink) {
            e.preventDefault();
            loadCreateModal();
        }
    });
    
    // Event delegation for edit post shortcode clicks
    document.body.addEventListener('click', function(e) {
        const editPostLink = e.target.closest('[data-edit-post]');
        if (editPostLink) {
            e.preventDefault();
            const postId = editPostLink.getAttribute('data-edit-post');
            const postType = editPostLink.getAttribute('data-post-type');
            
            if (postId && postType) {
                // Open modal with loading state
                if (window.openModal) {
                    window.openModal('<div class="loading">Loading...</div>');
                }
                loadPostCreationForm(postType, postId);
            }
        }
    });
    
    // Event delegation for delete post shortcode clicks
    document.body.addEventListener('click', function(e) {
        const deletePostLink = e.target.closest('[data-delete-post]');
        if (deletePostLink) {
            e.preventDefault();
            const postId = deletePostLink.getAttribute('data-delete-post');
            const postType = deletePostLink.getAttribute('data-post-type');
            
            if (postId && postType) {
                showDeleteConfirmation(postId, postType);
            }
        }
    });
    
    // Show styled delete confirmation modal
    function showDeleteConfirmation(postId, postType) {
        const postTypeLabel = postType === '1hrphoto' ? '1 Hour Photo' : 'Story';
        const html = `
            <div class="delete-confirmation">
                <h2>Delete ${postTypeLabel}?</h2>
                <p>This action will move your post to trash. You can restore it from your WordPress admin.</p>
                <div class="delete-actions">
                    <button type="button" class="btn-cancel" onclick="window.closeModal && window.closeModal()">Cancel</button>
                    <button type="button" class="btn-delete" data-post-id="${postId}">Delete</button>
                </div>
            </div>
        `;
        
        if (window.openModal) {
            window.openModal(html);
        }
        
        // Handle delete button click
        setTimeout(() => {
            const deleteBtn = document.querySelector('.btn-delete[data-post-id="' + postId + '"]');
            if (deleteBtn) {
                deleteBtn.addEventListener('click', function() {
                    deletePost(postId);
                });
            }
        }, 100);
    }
    
    // Delete post via AJAX
    async function deletePost(postId) {
        const deleteBtn = document.querySelector('.btn-delete[data-post-id="' + postId + '"]');
        if (deleteBtn) {
            deleteBtn.textContent = 'Deleting...';
            deleteBtn.disabled = true;
        }
        
        try {
            const response = await fetch('/wp-admin/admin-ajax.php', {
                method: 'POST',
                body: new URLSearchParams({
                    action: 'delete_post',
                    post_id: postId,
                    nonce: ajax_object.nonce
                })
            });
            
            const data = await response.json();
            
            if (data.success) {
                // Close modal
                if (window.closeModal) {
                    window.closeModal();
                }
                
                // Redirect to referrer or home
                const referrer = document.referrer;
                if (referrer && referrer.indexOf(window.location.host) !== -1 && referrer !== window.location.href) {
                    window.location.href = referrer;
                } else {
                    window.location.href = '/';
                }
            } else {
                alert(data.data || 'Failed to delete post');
                if (deleteBtn) {
                    deleteBtn.textContent = 'Delete';
                    deleteBtn.disabled = false;
                }
            }
        } catch (error) {
            console.error('Error deleting post:', error);
            alert('Failed to delete post');
            if (deleteBtn) {
                deleteBtn.textContent = 'Delete';
                deleteBtn.disabled = false;
            }
        }
    }
    
    // Event delegation for red X image removal buttons (Story featured image only)
    document.body.addEventListener('click', function(e) {
        const removeBtn = e.target.closest('.remove-image');
        if (removeBtn) {
            e.preventDefault();
            const attachmentId = removeBtn.getAttribute('data-attachment-id');
            const preview = removeBtn.closest('.acf-image-preview');
            
            if (attachmentId && preview) {
                // Remove from UI immediately
                preview.remove();
                
                // Clear featured image field (this is for story featured image)
                const featuredField = document.querySelector('#featured-image-id');
                if (featuredField) {
                    featuredField.value = '';
                    const captionField = document.querySelector('#featured-image-caption');
                    if (captionField) {
                        captionField.value = '';
                    }
                    
                    // Update button text
                    const uploaderBtn = document.querySelector('#story-featured-uploader');
                    if (uploaderBtn) {
                        uploaderBtn.textContent = 'Add featured image';
                    }
                }
            }
        }
    });
    
    // Handle ACF image field clicks - direct AJAX upload (no media frame)
    document.body.addEventListener('click', function(e) {
        const addBtn = e.target.closest('.acf-button[data-name="add"]');
        if (!addBtn) return;

        e.preventDefault();

        const fieldContainer = addBtn.closest('.acf-field-image');
        if (!fieldContainer) return;

        const hiddenInput = fieldContainer.querySelector('input[type="hidden"]');
        if (!hiddenInput || !hiddenInput.name || !hiddenInput.name.startsWith('acf[')) return;

        try { console.log('Intercepted ACF Add Image click'); } catch (e) {}

        const fileInput = document.createElement('input');
        fileInput.type = 'file';
        fileInput.accept = 'image/*';
        fileInput.style.display = 'none';

        // Attach to body to avoid DOM removal issues
        document.body.appendChild(fileInput);

        fileInput.addEventListener('change', async function() {
            const file = fileInput.files && fileInput.files[0];
            if (!file) return;

            if (!file.type.startsWith('image/')) {
                alert('Please select an image file.');
                fileInput.remove();
                return;
            }

            if (file.size > 5 * 1024 * 1024) {
                alert('File size must be less than 5MB.');
                fileInput.remove();
                return;
            }

            const data = new FormData();
            data.append('action', 'upload_acf_image');
            data.append('nonce', ajax_object.nonce);
            data.append('file', file);

            try {
                const res = await fetch(ajax_object.ajax_url, {
                    method: 'POST',
                    body: data,
                    credentials: 'same-origin'
                });
                const json = await res.json();
                if (!json || !json.success) {
                    alert((json && json.data && json.data.message) || 'Upload failed.');
                    fileInput.remove();
                    return;
                }

                const attachmentId = json.data.attachment_id;
                const thumbnailUrl = json.data.thumbnail_url;

                // Set hidden input to attachment ID so ACF stores it on submit
                hiddenInput.value = attachmentId;

                // Show preview
                let imageWrap = fieldContainer.querySelector('.image-wrap');
                if (!imageWrap) {
                    imageWrap = document.createElement('div');
                    imageWrap.className = 'image-wrap';
                    fieldContainer.appendChild(imageWrap);
                }
                let img = imageWrap.querySelector('img');
                if (!img) {
                    img = document.createElement('img');
                    img.style.maxWidth = '150px';
                    img.style.height = 'auto';
                    imageWrap.appendChild(img);
                }
                img.src = thumbnailUrl;

                // Hide add button and add remove button
                addBtn.style.display = 'none';
                let removeBtn = fieldContainer.querySelector('.acf-remove-upload');
                if (!removeBtn) {
                    removeBtn = document.createElement('button');
                    removeBtn.type = 'button';
                    removeBtn.className = 'acf-remove-upload';
                    removeBtn.textContent = 'Remove';
                    removeBtn.style.marginTop = '6px';
                    fieldContainer.appendChild(removeBtn);

                    removeBtn.addEventListener('click', function() {
                        hiddenInput.value = '';
                        if (imageWrap) imageWrap.remove();
                        addBtn.style.display = '';
                        removeBtn.remove();
                    });
                }
            } catch (err) {
                alert('Upload failed.');
            } finally {
                fileInput.remove();
            }
        });

        // Ensure click occurs in user-gesture context and is robust across browsers
        try {
            fileInput.click();
        } catch (err) {
            try { fileInput.focus(); fileInput.click(); } catch (e2) {}
        }
    }, true); // capture to run before ACF handlers

    // 1hrphoto single-uploader: 3 images, reorder, map to acf[1hrpic1..3]
    function init1hrphotoUploaderIfPresent(container) {
        const form = container.querySelector('.post-creation-form[data-post-type="1hrphoto"]');
        if (!form) return;

        const thumbsRow = form.querySelector('#thumbs-row');
        const uploadBtn = form.querySelector('#one-uploader');
        const hiddenInputs = [
            form.querySelector('#acf-1hrpic1'),
            form.querySelector('#acf-1hrpic2'),
            form.querySelector('#acf-1hrpic3')
        ];
        if (!thumbsRow || !uploadBtn || hiddenInputs.some(i => !i)) return;

        const srcById = new Map();
        let orderedIds = [];

        function syncHiddenInputs() {
            hiddenInputs.forEach((input, i) => {
                input.value = orderedIds[i] || '';
            });
        }

        function renderThumb(id, url) {
            const item = document.createElement('div');
            item.className = 'thumb-item';
            item.draggable = true;
            item.dataset.attachmentId = String(id);

            const img = document.createElement('img');
            img.src = url;
            img.alt = 'Selected image';
            img.loading = 'lazy';
            item.appendChild(img);
            
            // Add red X remove button
            const removeBtn = document.createElement('button');
            removeBtn.type = 'button';
            removeBtn.className = 'remove-thumb-btn';
            removeBtn.innerHTML = '×';
            removeBtn.setAttribute('aria-label', 'Remove image');
            removeBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                e.preventDefault();
                // Remove from orderedIds and srcById
                const index = orderedIds.indexOf(id);
                if (index > -1) {
                    orderedIds.splice(index, 1);
                    srcById.delete(id);
                    rebuildThumbs();
                    syncHiddenInputs();
                }
            });
            item.appendChild(removeBtn);

            // Drag handlers
            item.addEventListener('dragstart', e => {
                e.dataTransfer.effectAllowed = 'move';
                e.dataTransfer.setData('text/plain', item.dataset.attachmentId);
                item.classList.add('dragging');
            });
            item.addEventListener('dragend', () => item.classList.remove('dragging'));
            item.addEventListener('dragover', e => e.preventDefault());
            item.addEventListener('drop', e => {
                e.preventDefault();
                const draggedId = e.dataTransfer.getData('text/plain');
                const targetId = item.dataset.attachmentId;
                if (!draggedId || !targetId || draggedId === targetId) return;
                const from = orderedIds.indexOf(Number(draggedId));
                const to = orderedIds.indexOf(Number(targetId));
                if (from < 0 || to < 0) return;
                const [moved] = orderedIds.splice(from, 1);
                orderedIds.splice(to, 0, moved);
                rebuildThumbs();
                syncHiddenInputs();
            });

            thumbsRow.appendChild(item);
        }

        function rebuildThumbs() {
            thumbsRow.innerHTML = '';
            orderedIds.forEach(id => {
                const url = srcById.get(id);
                renderThumb(id, url);
            });
        }

        uploadBtn.addEventListener('click', () => {
            if (orderedIds.length >= 3) { alert('Exactly 3 images are required. Remove one to replace.'); return; }
            const input = document.createElement('input');
            input.type = 'file';
            input.accept = 'image/*';
            input.multiple = true;
            input.style.display = 'none';
            document.body.appendChild(input);

            input.addEventListener('change', async () => {
                const files = Array.from(input.files || []);
                for (const file of files) {
                    if (orderedIds.length >= 3) break;
                    if (!file.type.startsWith('image/')) continue;
                    // JPEG-only client guard
                    const nameLower = (file.name || '').toLowerCase();
                    if (!(nameLower.endsWith('.jpg') || nameLower.endsWith('.jpeg')) || file.type !== 'image/jpeg') {
                        alert('Only JPG/JPEG files are allowed.');
                        continue;
                    }
                    // Max file size 200KB
                    if (file.size > 200 * 1024) {
                        const err = form.querySelector('#acf-1hrpics-error');
                        if (err) { err.textContent = 'Maximum file size must not exceed 200KB'; err.style.display = 'block'; }
                        continue;
                    }

                    // Max height 1000px (check client-side before upload)
                    const dims = await (async () => {
                        return new Promise(resolve => {
                            const img = new Image();
                            img.onload = function(){ resolve({ width: img.naturalWidth || img.width, height: img.naturalHeight || img.height }); };
                            img.onerror = function(){ resolve({ width: 0, height: 0 }); };
                            img.src = URL.createObjectURL(file);
                        });
                    })();
                    if (dims && dims.height && dims.height > 1000) {
                        const err = form.querySelector('#acf-1hrpics-error');
                        if (err) { err.textContent = 'Maximum height must not exceed 1,000 pixels'; err.style.display = 'block'; }
                        continue;
                    }

                    const data = new FormData();
                    data.append('action', 'upload_acf_image');
                    data.append('nonce', ajax_object.nonce);
                    data.append('file', file);

                    try {
                        const res = await fetch(ajax_object.ajax_url, { method: 'POST', body: data, credentials: 'same-origin' });
                        const json = await res.json();
                        if (!json || !json.success) { alert((json && json.data && json.data.message) || 'Upload failed'); continue; }
                        const id = Number(json.data.attachment_id);
                        const url = json.data.thumbnail_url;
                        if (!orderedIds.includes(id)) {
                            orderedIds.push(id);
                            srcById.set(id, url);
                            renderThumb(id, url);
                            syncHiddenInputs();
                            const err = form.querySelector('#acf-1hrpics-error');
                            if (err) { err.textContent = ''; err.style.display = 'none'; }
                        }
                    } catch (e) {
                        alert('Upload failed.');
                    }
                }
                input.remove();
            });

            input.click();
        });

        form.addEventListener('submit', e => {
            if (orderedIds.length !== 3) {
                e.preventDefault();
                const err = form.querySelector('#acf-1hrpics-error');
                if (err) { err.textContent = 'You need to upload three images.'; err.style.display = 'block'; }
            }
        });
        
        // Load existing images in edit mode (must be after function definitions)
        const editMode = thumbsRow.getAttribute('data-edit-mode') === '1';
        if (editMode) {
            try {
                const existingData = thumbsRow.getAttribute('data-existing-images');
                if (existingData) {
                    const existing = JSON.parse(existingData);
                    if (Array.isArray(existing)) {
                        existing.forEach(item => {
                            if (item.id && item.url) {
                                orderedIds.push(Number(item.id));
                                srcById.set(Number(item.id), item.url);
                            }
                        });
                        rebuildThumbs();
                        syncHiddenInputs();
                    }
                }
            } catch (e) {
                console.error('Failed to load existing images:', e);
            }
        }
    }
    // Fallback: dedicated button next to image field (Upload from device)
    document.body.addEventListener('click', function(e) {
        const uploadBtn = e.target.closest('.acf-direct-upload');
        if (!uploadBtn) return;

        e.preventDefault();

        const fieldKey = uploadBtn.getAttribute('data-field-key');
        const fieldName = uploadBtn.getAttribute('data-field-name');
        try { console.log('Direct upload button clicked', { fieldKey, fieldName }); } catch (e) {}

        // Resolve the actual ACF image field container
        let fieldContainer = null;
        const scope = uploadBtn.closest('form') || document;
        if (fieldKey) {
            fieldContainer = scope.querySelector('.acf-field-image[data-key="' + fieldKey + '"]');
        }
        if (!fieldContainer && fieldName) {
            fieldContainer = scope.querySelector('.acf-field-image[data-name="' + fieldName + '"]');
        }
        if (!fieldContainer) {
            const localWrapper = uploadBtn.closest('.form-field') || uploadBtn.closest('.acf-field');
            fieldContainer = localWrapper ? localWrapper.querySelector('.acf-field-image') : null;
        }
        if (!fieldContainer) { try { console.log('Direct upload: image field container not found'); } catch (e) {} }

        // Prefer finding hidden input by exact field name within the form scope
        let hiddenInput = null;
        if (fieldName) {
            hiddenInput = scope.querySelector('input[type="hidden"][name="acf[' + fieldName + ']"]');
        }
        if (!hiddenInput && fieldContainer) {
            hiddenInput = fieldContainer.querySelector('input[type="hidden"][name^="acf["]');
        }
        if (!hiddenInput) { try { console.log('Direct upload: hidden input not found'); } catch (e) {} }

        const fileInput = document.createElement('input');
        fileInput.type = 'file';
        fileInput.accept = 'image/*';
        fileInput.style.display = 'none';
        document.body.appendChild(fileInput);

        fileInput.addEventListener('change', async function() {
            const file = fileInput.files && fileInput.files[0];
            if (!file) return;
            if (!file.type.startsWith('image/')) { alert('Please select an image file.'); fileInput.remove(); return; }
            if (file.size > 5 * 1024 * 1024) { alert('File size must be less than 5MB.'); fileInput.remove(); return; }

            // Immediate local preview for user feedback
            try {
                const reader = new FileReader();
                reader.onload = function(ev) {
                    // Insert preview right after the upload button
                    let existingWrap = uploadBtn.nextElementSibling && uploadBtn.nextElementSibling.classList && uploadBtn.nextElementSibling.classList.contains('image-wrap') ? uploadBtn.nextElementSibling : null;
                    if (!existingWrap) {
                        existingWrap = document.createElement('div');
                        existingWrap.className = 'image-wrap';
                        uploadBtn.insertAdjacentElement('afterend', existingWrap);
                    }
                    let previewImg = existingWrap.querySelector('img');
                    if (!previewImg) {
                        previewImg = document.createElement('img');
                        previewImg.style.maxWidth = '150px';
                        previewImg.style.height = 'auto';
                        existingWrap.appendChild(previewImg);
                    }
                    previewImg.src = ev.target.result;
                };
                reader.readAsDataURL(file);
            } catch (e) {}

            const data = new FormData();
            data.append('action', 'upload_acf_image');
            data.append('nonce', ajax_object.nonce);
            data.append('file', file);

            try {
                const res = await fetch(ajax_object.ajax_url, { method: 'POST', body: data, credentials: 'same-origin' });
                const json = await res.json();
                if (!json || !json.success) { alert((json && json.data && json.data.message) || 'Upload failed.'); return; }

                const attachmentId = json.data.attachment_id;
                const thumbnailUrl = json.data.thumbnail_url;

                if (hiddenInput) {
                    hiddenInput.value = attachmentId;
                } else if (fieldName) {
                    const form = uploadBtn.closest('form');
                    if (form) {
                        const createdHidden = document.createElement('input');
                        createdHidden.type = 'hidden';
                        createdHidden.name = 'acf[' + fieldName + ']';
                        createdHidden.value = attachmentId;
                        form.appendChild(createdHidden);
                    }
                }

                // Replace local preview with server thumbnail (ensures consistent sizing)
                let inlineWrap = uploadBtn.nextElementSibling && uploadBtn.nextElementSibling.classList && uploadBtn.nextElementSibling.classList.contains('image-wrap') ? uploadBtn.nextElementSibling : null;
                if (!inlineWrap) {
                    inlineWrap = document.createElement('div');
                    inlineWrap.className = 'image-wrap';
                    uploadBtn.insertAdjacentElement('afterend', inlineWrap);
                }
                let inlineImg = inlineWrap.querySelector('img');
                if (!inlineImg) {
                    inlineImg = document.createElement('img');
                    inlineImg.style.maxWidth = '150px';
                    inlineImg.style.height = 'auto';
                    inlineWrap.appendChild(inlineImg);
                }
                inlineImg.src = thumbnailUrl;
            } catch (err) {
                alert('Upload failed.');
            } finally {
                fileInput.remove();
            }
        });

        try { fileInput.click(); } catch (err) { try { fileInput.focus(); fileInput.click(); } catch(e2) {} }
    }, true);

    // Story: featured image uploader
    function initStoryFeaturedIfPresent(container) {
        const form = container.querySelector('.post-creation-form[data-post-type="story"]');
        if (!form) return;
        const btn = form.querySelector('#story-featured-uploader');
        const preview = form.querySelector('#story-featured-preview');
        const hiddenId = form.querySelector('#featured-image-id');
        const captionInput = form.querySelector('#featured-image-caption');
        if (!btn || !preview || !hiddenId) return;

        btn.addEventListener('click', () => {
            const input = document.createElement('input');
            input.type = 'file';
            input.accept = 'image/jpeg,image/jpg';
            input.style.display = 'none';
            document.body.appendChild(input);
            input.addEventListener('change', async () => {
                const file = input.files && input.files[0];
                if (!file) { input.remove(); return; }
                // JPEG-only and constraints
                const nameLower = (file.name || '').toLowerCase();
                if (!(nameLower.endsWith('.jpg') || nameLower.endsWith('.jpeg')) || file.type !== 'image/jpeg') {
                    alert('Only JPG/JPEG files are allowed.'); input.remove(); return;
                }
                if (file.size > 200 * 1024) { alert('Maximum file size must not exceed 200KB'); input.remove(); return; }
                const dims = await new Promise(resolve => {
                    const img = new Image();
                    img.onload = () => resolve({ h: img.naturalHeight || img.height });
                    img.onerror = () => resolve({ h: 0 });
                    img.src = URL.createObjectURL(file);
                });
                if ((dims.h || 0) > 1000) { alert('Maximum height must not exceed 1,000 pixels'); input.remove(); return; }

                const data = new FormData();
                data.append('action', 'upload_acf_image');
                data.append('nonce', ajax_object.nonce);
                data.append('file', file);
                try {
                    const res = await fetch(ajax_object.ajax_url, { method: 'POST', body: data, credentials: 'same-origin' });
                    const json = await res.json();
                    if (!json || !json.success) { alert((json && json.data && json.data.message) || 'Upload failed.'); input.remove(); return; }
                    hiddenId.value = String(json.data.attachment_id);
                    // preview as full-width figure with caption and remove
                    preview.innerHTML = '';
                    const fig = document.createElement('figure');
                    fig.className = 'story-featured-figure';
                    const imgEl = document.createElement('img');
                    imgEl.src = json.data.thumbnail_url;
                    imgEl.alt = 'Featured image';
                    const cap = document.createElement('figcaption');
                    cap.contentEditable = 'true';
                    cap.setAttribute('aria-label', 'Featured image caption');
                    cap.addEventListener('input', () => {
                        if (captionInput) captionInput.value = cap.textContent.trim();
                    });
                    const remove = document.createElement('button');
                    remove.type = 'button';
                    remove.className = 'story-thumb-remove';
                    remove.setAttribute('aria-label', 'Remove image');
                    remove.title = 'Remove image';
                    remove.textContent = '×';
                    remove.addEventListener('click', (e) => {
                        e.preventDefault();
                        hiddenId.value = '';
                        if (captionInput) captionInput.value = '';
                        preview.innerHTML = '';
                        btn.style.display = '';
                    });
                    fig.append(imgEl, cap, remove);
                    preview.appendChild(fig);
                    btn.style.display = 'none';
                } catch (e) { alert('Upload failed.'); } finally { input.remove(); }
            });
            input.click();
        });
    }

    // Story: simple editor
    function initStoryEditorIfPresent(container) {
        const form = container.querySelector('.post-creation-form[data-post-type="story"]');
        if (!form) return;
        const editor = form.querySelector('#story-editor');
        const contentField = form.querySelector('#post-content');
        const imagesField = form.querySelector('#story-images-json');
        const counterEl = form.querySelector('#story-image-count');
        const wordCounterEl = form.querySelector('#story-word-count');
        if (!editor || !contentField || !imagesField) return;

        function countWordsExcludingCaptions(root) {
            // Clone and strip figures/captions, then count words in remaining text
            const clone = root.cloneNode(true);
            clone.querySelectorAll('figure').forEach(f => f.remove());
            const text = clone.textContent || '';
            const words = (text.trim().match(/\b\w+\b/g) || []).length;
            return words;
        }

        function syncHidden() {
            contentField.value = editor.innerHTML;
            // Build ordered images list (prefer figures; fallback to imgs with data-attachment-id)
            const images = [];
            const seen = new Set();
            const figs = editor.querySelectorAll('figure[data-attachment-id]');
            figs.forEach(fig => {
                const id = Number(fig.getAttribute('data-attachment-id')) || 0;
                const cap = (fig.querySelector('figcaption') || {}).textContent || '';
                if (id && !seen.has(id)) { images.push({ id, caption: cap.trim() }); seen.add(id); }
            });
            if (images.length === 0) {
                editor.querySelectorAll('img[data-attachment-id]').forEach(img => {
                    const id = Number(img.getAttribute('data-attachment-id')) || 0;
                    if (id && !seen.has(id)) { images.push({ id, caption: '' }); seen.add(id); }
                });
            }
            imagesField.value = JSON.stringify(images);
            // Update live counter
            if (counterEl) {
                const n = images.length;
                counterEl.textContent = n + '/6 minimum images inserted (max 10)';
                if (n >= 6 && n <= 10) {
                    counterEl.classList.add('is-ok');
                } else {
                    counterEl.classList.remove('is-ok');
                }
            }
            // Update word counter (exclude captions)
            if (wordCounterEl) {
                const words = countWordsExcludingCaptions(editor);
                wordCounterEl.textContent = words + '/500 words (max 2000)';
                if (words >= 500 && words <= 2000) {
                    wordCounterEl.classList.add('is-ok');
                } else {
                    wordCounterEl.classList.remove('is-ok');
                }
            }
        }
        editor.addEventListener('input', syncHidden);
        editor.addEventListener('blur', syncHidden);
        editor.addEventListener('keyup', syncHidden);
        const mo = new MutationObserver(() => syncHidden());
        mo.observe(editor, { childList: true, subtree: true, characterData: true });

        // Toolbar actions
        const btnBold = form.querySelector('.btn-story-bold');
        const btnItalic = form.querySelector('.btn-story-italic');
        const btnHeading = form.querySelector('.btn-story-heading');
        const btnLink = form.querySelector('.btn-story-link');
        const btnInsert = form.querySelector('.btn-story-insert-image');

        // Update toolbar button states based on current selection
        function updateToolbarState() {
            if (!btnBold || !btnItalic || !btnHeading) return;
            
            try {
                // Check if bold is active
                const isBold = document.queryCommandState('bold');
                btnBold.classList.toggle('active', isBold);
                
                // Check if italic is active
                const isItalic = document.queryCommandState('italic');
                btnItalic.classList.toggle('active', isItalic);
                
                // Check if heading is active
                const formatBlock = document.queryCommandValue('formatBlock');
                const isHeading = formatBlock && /h[1-6]/i.test(formatBlock);
                btnHeading.classList.toggle('active', isHeading);
            } catch (e) {
                // queryCommandState can throw in some browsers
            }
        }
        
        // Update button states on selection change
        editor.addEventListener('mouseup', updateToolbarState);
        editor.addEventListener('keyup', updateToolbarState);
        editor.addEventListener('focus', updateToolbarState);
        
        function surround(tag) {
            document.execCommand('styleWithCSS', false, false);
            document.execCommand(tag === 'strong' ? 'bold' : 'italic', false);
            editor.focus();
            syncHidden();
            updateToolbarState();
        }
        btnBold && btnBold.addEventListener('click', () => surround('strong'));
        btnItalic && btnItalic.addEventListener('click', () => surround('em'));

        btnHeading && btnHeading.addEventListener('click', () => {
            // Toggle current block between p and h2
            document.execCommand('formatBlock', false, 'h2');
            editor.focus();
            syncHidden();
            updateToolbarState();
        });

        btnLink && btnLink.addEventListener('click', () => {
            const url = prompt('Enter URL');
            if (!url) return;
            document.execCommand('createLink', false, url);
            // Add rel/target to newly created links
            editor.querySelectorAll('a[href="' + CSS.escape(url) + '"]').forEach(a => { a.target = '_blank'; a.rel = 'noopener'; });
            editor.focus();
            syncHidden();
            updateToolbarState();
        });

        btnInsert && btnInsert.addEventListener('click', () => {
            const input = document.createElement('input');
            input.type = 'file';
            input.accept = 'image/jpeg,image/jpg';
            input.style.display = 'none';
            document.body.appendChild(input);
            input.addEventListener('change', async () => {
                const file = input.files && input.files[0];
                if (!file) { input.remove(); return; }
                const nameLower = (file.name || '').toLowerCase();
                if (!(nameLower.endsWith('.jpg') || nameLower.endsWith('.jpeg')) || file.type !== 'image/jpeg') { alert('Only JPG/JPEG files are allowed.'); input.remove(); return; }
                if (file.size > 200 * 1024) { alert('Maximum file size must not exceed 200KB'); input.remove(); return; }
                const dims = await new Promise(resolve => { const i=new Image(); i.onload=()=>resolve({h:i.naturalHeight||i.height}); i.onerror=()=>resolve({h:0}); i.src=URL.createObjectURL(file); });
                if ((dims.h||0) > 1000) { alert('Maximum height must not exceed 1,000 pixels'); input.remove(); return; }

                const data = new FormData();
                data.append('action', 'upload_acf_image');
                data.append('nonce', ajax_object.nonce);
                data.append('file', file);
                try {
                    const res = await fetch(ajax_object.ajax_url, { method: 'POST', body: data, credentials: 'same-origin' });
                    const json = await res.json();
                    if (!json || !json.success) { alert((json && json.data && json.data.message) || 'Upload failed.'); input.remove(); return; }
                    const id = Number(json.data.attachment_id);
                    const url = json.data.thumbnail_url;
                    // Insert figure at caret
                    editor.focus();
                    const fig = document.createElement('figure');
                    fig.setAttribute('data-attachment-id', String(id));
                    const img = document.createElement('img');
                    img.src = url; img.alt = '';
                    img.setAttribute('data-attachment-id', String(id));
                    const cap = document.createElement('figcaption');
                    cap.contentEditable = 'true';
                    cap.setAttribute('aria-label', 'Image caption');
                    cap.addEventListener('input', syncHidden);
                    fig.appendChild(img); fig.appendChild(cap);
                    // Add remove button (X) in top-right
                    const remove = document.createElement('button');
                    remove.type = 'button';
                    remove.className = 'story-thumb-remove';
                    remove.setAttribute('aria-label', 'Remove image');
                    remove.title = 'Remove image';
                    remove.textContent = '×';
                    fig.appendChild(remove);
                    // Make figure/img non-editable; caption is the only editable part
                    fig.setAttribute('contenteditable', 'false');
                    img.setAttribute('contenteditable', 'false');
                    cap.setAttribute('contenteditable', 'true');
                    cap.tabIndex = 0;
                    // Click image focuses caption
                    img.addEventListener('click', () => { cap.focus(); });
                    // Caption key handling: Enter exits to new paragraph; Shift+Enter adds line break
                    cap.addEventListener('keydown', (e) => {
                        if (e.key === 'Enter' && !e.shiftKey) {
                            e.preventDefault();
                            // insert paragraph after figure and move caret
                            const p = document.createElement('p'); p.innerHTML = '<br>'; // empty line
                            fig.insertAdjacentElement('afterend', p);
                            // place caret in the new paragraph
                            const sel = window.getSelection();
                            const r = document.createRange();
                            r.selectNodeContents(p); r.collapse(true);
                            sel.removeAllRanges(); sel.addRange(r);
                            editor.dispatchEvent(new Event('input')); // update hidden fields
                        }
                        // Backspace at start moves caret before figure
                        if (e.key === 'Backspace') {
                            const range = window.getSelection()?.getRangeAt(0);
                            const atStart = range && range.startOffset === 0 && range.startContainer === cap.firstChild;
                            if (atStart) {
                                e.preventDefault();
                                const br = document.createElement('p'); br.innerHTML = '<br>';
                                fig.insertAdjacentElement('beforebegin', br);
                                const sel = window.getSelection();
                                const r = document.createRange();
                                r.selectNodeContents(br); r.collapse(true);
                                sel.removeAllRanges(); sel.addRange(r);
                                editor.dispatchEvent(new Event('input'));
                            }
                        }
                    });
                    // Remove handler
                    function removeFigure() {
                        // create a new paragraph placeholder where figure was
                        const p = document.createElement('p'); p.innerHTML = '<br>';
                        fig.insertAdjacentElement('afterend', p);
                        fig.remove();
                        // move caret into paragraph and sync
                        const sel2 = window.getSelection();
                        const r2 = document.createRange();
                        r2.selectNodeContents(p); r2.collapse(true);
                        sel2.removeAllRanges(); sel2.addRange(r2);
                        syncHidden();
                    }
                    remove.addEventListener('click', (e) => { e.preventDefault(); e.stopPropagation(); removeFigure(); });
                    remove.addEventListener('keydown', (e) => {
                        if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); removeFigure(); }
                    });

                    // insert
                    editor.focus();
                    const sel = window.getSelection();
                    if (sel && sel.rangeCount) {
                        const range = sel.getRangeAt(0);
                        let node = range.startContainer;
                        // find nearest <p>
                        let anc = node.nodeType === 1 ? node : node.parentNode;
                        while (anc && anc !== editor && (!anc.tagName || anc.tagName !== 'P')) { anc = anc.parentNode; }
                        const isEmptyP = (p) => !!p && p.tagName === 'P' && (p.textContent.trim() === '' && (p.children.length === 0 || (p.children.length === 1 && p.children[0].tagName === 'BR')));
                        if (isEmptyP(anc)) {
                            // Insert after the empty paragraph so it remains as spacer
                            anc.insertAdjacentElement('afterend', fig);
                        } else {
                            range.collapse(false);
                            range.insertNode(fig);
                        }
                    } else {
                        editor.appendChild(fig);
                    }
                    // Always add a new paragraph after this figure for typing
                    const p = document.createElement('p'); p.innerHTML = '<br>';
                    fig.insertAdjacentElement('afterend', p);
                    const r = document.createRange(); r.selectNodeContents(p); r.collapse(true);
                    const s2 = window.getSelection(); s2.removeAllRanges(); s2.addRange(r);
                    requestAnimationFrame(() => {
                        editor.focus();
                        const s3 = window.getSelection(); const r3 = document.createRange();
                        r3.selectNodeContents(p); r3.collapse(true);
                        s3.removeAllRanges(); s3.addRange(r3);
                    });
                    syncHidden();
                } catch (e) { alert('Upload failed.'); } finally { input.remove(); }
            });
            input.click();
        });

        // Keyboard shortcuts to minimize toolbar reaching
        form.addEventListener('keydown', (e) => {
            const meta = e.metaKey || e.ctrlKey;
            if (!meta) return;
            const k = e.key.toLowerCase();
            // Bold: Cmd/Ctrl+B
            if (k === 'b' && !e.shiftKey) { e.preventDefault(); btnBold && btnBold.click(); return; }
            // Italic: Cmd/Ctrl+I
            if (k === 'i' && !e.shiftKey) { e.preventDefault(); btnItalic && btnItalic.click(); return; }
            // Heading: Cmd/Ctrl+2
            if (e.key === '2') { e.preventDefault(); btnHeading && btnHeading.click(); return; }
            // Link: Cmd/Ctrl+K
            if (k === 'k') { e.preventDefault(); btnLink && btnLink.click(); return; }
            // Insert image: Cmd/Ctrl+Shift+I
            if (k === 'i' && e.shiftKey) { e.preventDefault(); btnInsert && btnInsert.click(); return; }
        });
        // Client-side Story validations on submit
        form.addEventListener('submit', (e) => {
            // Update hidden JSON first
            syncHidden();
            // Normalize editor HTML so saved content does not include medium <img> thumbnails
            try {
                const clone = editor.cloneNode(true);
                clone.querySelectorAll('figure[data-attachment-id] img').forEach(img => img.remove());
                contentField.value = clone.innerHTML;
            } catch (err) {}
            const featuredId = (form.querySelector('#featured-image-id') || {}).value || '';
            if (!featuredId) {
                const er = form.querySelector('#featured_image_id-error');
                if (er) { er.textContent = 'Featured image is required.'; er.style.display = 'block'; }
                e.preventDefault();
                return;
            }
            // Validate count based on synced JSON (source of truth)
            let imagesList = [];
            try { imagesList = JSON.parse(imagesField.value || '[]') || []; } catch (e) { imagesList = []; }
            const count = imagesList.length;
            if (count < 6 || count > 10) {
                const er = form.querySelector('#story_images-error');
                if (er) { er.textContent = 'Please add between 6 and 10 images.'; er.style.display = 'block'; }
                e.preventDefault();
                return;
            }
            // Enforce 500–2000 words excluding captions
            const words = countWordsExcludingCaptions(editor);
            if (words < 500 || words > 2000) {
                const er = form.querySelector('#post_content-error');
                if (er) { er.textContent = 'Main body must be between 500 and 2000 words.'; er.style.display = 'block'; }
                e.preventDefault();
                return;
            }
        });
    }
});
