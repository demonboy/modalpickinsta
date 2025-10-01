document.addEventListener('DOMContentLoaded', () => {
  // Filled state SVG for comment icon (40x40), matches PHP shortcode filled color (#da5742)
  const FILLED_COMMENT_SVG = '<svg fill="#da5742" width="40" height="40" viewBox="0 0 1920 1920" xmlns="http://www.w3.org/2000/svg"><path d="M84 0v1423.143h437.875V1920l621.235-496.857h692.39V0z" fill-rule="evenodd"/></svg>';

  function setCommentIconFilled(postId) {
    const icons = document.querySelectorAll(`.icon-item.comment-icon[data-post-id="${postId}"]`);
    icons.forEach(icon => {
      const svg = icon.querySelector('svg');
      if (svg) {
        svg.outerHTML = FILLED_COMMENT_SVG;
      }
    });
  }

  // Comments loading function
  async function loadCommentsModal(postId) {
    try {
      // Use the universal modal to show loading
      if (window.openModal) {
        window.openModal('<div class="loading">Loading comments...</div>');
      }
      
      const response = await fetch('/wp-admin/admin-ajax.php', {
        method: 'POST',
        body: new URLSearchParams({
          action: 'get_comments_modal',
          post_id: postId,
          nonce: post_likes_ajax.nonce
        })
      });
      const data = await response.json();
      
      if (data.success) {
        // Store the post ID in the modal for later use
        const modal = document.getElementById('universal-modal');
        if (modal) {
          modal.setAttribute('data-post-id', postId);
        }
        
        // Use the universal modal to show comments
        if (window.openModal) {
          // Mark this modal instance as the comments modal so we can scope UI tweaks
          if (window.setActiveModal) { window.setActiveModal('comments'); }

          const dn = (data && data.data && data.data.author_display) ? String(data.data.author_display).replace(/[&<>"']/g, s => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;','\'':'&#39;'}[s])) : '';
          
          // Create critique welcome button HTML (matching the shortcode output)
          const critiqueBtn = data && data.data && data.data.wants_cf ? `<button class="critique-welcome-btn critique-welcome-btn-inline comment-icon" data-post-id="${postId}">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M8.25 18L5.25 20.25V15.75H2.25C1.852 15.75 1.471 15.592 1.189 15.311C.908 15.029.75 14.648.75 14.25V2.25C.75 1.852.908 1.471 1.189 1.189C1.471.908 1.852.75 2.25.75H18.75C19.148.75 19.529.908 19.811 1.189C20.092 1.471 20.25 1.852 20.25 2.25V6.715" stroke="#71717A" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
              <path d="M5.25 5.25H15.75M5.25 9.75H8.25" stroke="#71717A" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
              <path d="M23.25 18.75H20.25V23.25L15.75 18.75H11.25V9.75H23.25V18.75Z" stroke="#71717A" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
              <path d="M19.5 15H15" stroke="#71717A" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <span class="critique-welcome-text">Critique welcome</span>
          </button>` : '';
          
          const note = (data && data.data && data.data.wants_cf) ? `<p>${dn} is looking for constructive feedback on this post. Use the ${critiqueBtn} button to submit your critique, otherwise leave your comment below.</p><p class="comment-spacer"></p>` : '';
          window.openModal(`<h2>Comments (${data.data.count})</h2>${note}${data.data.comments}`);

          // Cleanup: clear the active flag when the modal is closed via any route
          const modalEl = document.getElementById('universal-modal');
          let onEsc;
          const clearFlag = () => {
            try { window.setActiveModal && window.setActiveModal(''); } catch(_) {}
            try { document.removeEventListener('keydown', onEsc); } catch(_) {}
          };

          if (modalEl) {
            // Close button emits this event before closing
            try { modalEl.addEventListener('universal-modal-close', clearFlag, { once: true }); } catch(_) {}

            // Backdrop click path
            const backdrop = modalEl.querySelector('.modal-backdrop');
            if (backdrop) {
              const onBackdrop = () => { clearFlag(); backdrop.removeEventListener('click', onBackdrop); };
              backdrop.addEventListener('click', onBackdrop);
            }
          }

          // Escape key path
          onEsc = function(e) { if (e.key === 'Escape') { clearFlag(); } };
          document.addEventListener('keydown', onEsc);
        }
      } else {
        if (window.openModal) {
          window.openModal('<h2>Error</h2><p>Could not load comments.</p>');
        }
      }
    } catch (error) {
      if (window.openModal) {
        window.openModal('<h2>Error</h2><p>Could not load comments.</p>');
      }
    }
  }
  
  // Make function globally accessible
  window.loadCommentsModal = loadCommentsModal;

  // Update comment count function
  function updateCommentCount(postId) {
    // Get updated count from AJAX endpoint
    fetch('/wp-admin/admin-ajax.php', {
      method: 'POST',
      body: new URLSearchParams({
        action: 'get_comments_modal',
        post_id: postId,
        nonce: post_likes_ajax.nonce
      })
    })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          const newCount = data.data.count;
          
          // Update comment icons on the page
          const commentIcons = document.querySelectorAll(`[data-post-id="${postId}"]`);
          commentIcons.forEach(icon => {
            const countSpan = icon.querySelector('.comment-count');
            if (countSpan) {
              countSpan.textContent = newCount;
            }
          });
          
          // Update [comment_count] shortcodes for this post
          const commentCountShortcodes = document.querySelectorAll(`.comment-count-number[data-post-id="${postId}"]`);
          commentCountShortcodes.forEach(shortcode => {
            shortcode.textContent = newCount;
          });
          
          // Update modal header count (if modal is open for this post)
          const modal = document.getElementById('universal-modal');
          if (modal && modal.getAttribute('data-post-id') === postId) {
            const modalHeader = modal.querySelector('.modal-body h2');
            if (modalHeader) {
              // Replace the count in "Comments (X)" format
              modalHeader.textContent = modalHeader.textContent.replace(/\(\d+\)/, '(' + newCount + ')');
            }
          }
        }
      })
      .catch(error => {
        console.log('Error updating comment count:', error);
      });
  }

  // Pre-populate data-post-id on fake comment fields if missing (from nearest article)
  try {
    document.querySelectorAll('.comtrigfield').forEach(function(el){
      if (!el.getAttribute('data-post-id')) {
        var article = el.closest && el.closest('article[data-post-id]');
        if (article) {
          el.setAttribute('data-post-id', article.getAttribute('data-post-id'));
        } else {
          // Fallback: nearest ancestor with class 'wp-block-post post-<ID>' (common in Query Loop)
          try {
            var wrap = el.closest && el.closest('.wp-block-post');
            if (wrap && wrap.className) {
              var mm = String(wrap.className).match(/\bpost-(\d+)\b/);
              if (mm && mm[1]) { el.setAttribute('data-post-id', mm[1]); }
            }
          } catch(_) {}

          // Fallback: parse from body class postid-123 (single templates)
          try {
            var m = (document.body && document.body.className || '').match(/\bpostid-(\d+)\b/);
            if (m && m[1]) { el.setAttribute('data-post-id', m[1]); }
          } catch(_) {}
        }
      }
    });
  } catch(_) {}

  // Event delegation for comment icon clicks
  document.body.addEventListener('click', function(e) {
    const clicked = e.target.closest('.comment-icon');
    if (clicked) {
      console.log('Comment icon clicked!'); // Debug log
      
      // Get post ID from data attribute
      const postId = clicked.getAttribute('data-post-id');
      console.log('Post ID:', postId); // Debug log
      
      if (postId) {
        loadCommentsModal(postId);
      } else {
        if (window.openModal) {
          window.openModal('<h2>Error</h2><p>No post ID found. Add data-post-id attribute to comment icon.</p>');
        }
      }
    }
  });

  // Event delegation for fake comment field (open comments modal)
  document.body.addEventListener('click', function(e) {
    var trigger = e.target && e.target.closest && e.target.closest('.comtrigfield');
    if (!trigger) return;

    // Prevent default if inside a link or similar
    try { e.preventDefault(); } catch(_) {}

    // Resolve post ID: explicit on trigger → nearest article → nearest .wp-block-post class post-<ID> → any visible comment icon → body.postid-123
    var postId = trigger.getAttribute('data-post-id');
    if (!postId) {
      var article = trigger.closest && trigger.closest('article[data-post-id]');
      if (article) { postId = article.getAttribute('data-post-id'); }
    }
    if (!postId) {
      try {
        var wpPost = trigger.closest && trigger.closest('.wp-block-post');
        if (wpPost && wpPost.className) {
          var mm2 = String(wpPost.className).match(/\bpost-(\d+)\b/);
          if (mm2 && mm2[1]) { postId = mm2[1]; }
        }
      } catch(_) {}
    }
    if (!postId) {
      var icon = document.querySelector('.comment-icon[data-post-id]');
      if (icon) { postId = icon.getAttribute('data-post-id'); }
    }
    if (!postId) {
      try {
        var m2 = (document.body && document.body.className || '').match(/\bpostid-(\d+)\b/);
        if (m2 && m2[1]) { postId = m2[1]; }
      } catch(_) {}
    }

    if (postId) {
      loadCommentsModal(postId);
    } else if (window.openModal) {
      window.openModal('<h2>Error</h2><p>No post ID found. Add data-post-id to the fake comment field or wrap it in an article[data-post-id].</p>');
    }
  });

  // Prevent comment form redirect and refresh modal
  document.addEventListener('submit', function(e) {
    if (e.target.classList.contains('comment-form')) {
      e.preventDefault(); // Stop the redirect
      
      const formData = new FormData(e.target);
      const postId = formData.get('comment_post_ID');
      const comment = formData.get('comment');
      
      // Submit via AJAX
      fetch('/wp-admin/admin-ajax.php', {
        method: 'POST',
        body: new URLSearchParams({
          action: 'submit_comment',
          post_id: postId,
          comment: comment
        })
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          // Clear the comment input
          const commentInput = e.target.querySelector('input[name="comment"]');
          if (commentInput) {
            commentInput.value = '';
          }
          
          // Insert new comment HTML at the bottom of comments (before the sticky form)
          const modalBody = document.querySelector('#universal-modal .modal-body');
          if (modalBody && data.data.comment_html) {
            // Find the comment form and insert the new comment just before it
            const commentForm = modalBody.querySelector('#respond');
            if (commentForm) {
              commentForm.insertAdjacentHTML('beforebegin', data.data.comment_html);
            } else {
              // Fallback: insert at the end of modal body
              modalBody.insertAdjacentHTML('beforeend', data.data.comment_html);
            }
            
            // Scroll to the new comment
            setTimeout(() => {
              const newComment = modalBody.querySelector(`#comment-${data.data.comment_id}`);
              if (newComment) {
                newComment.scrollIntoView({ behavior: 'smooth', block: 'center' });
              }
            }, 100);
          }
          
          // Update comment count
          updateCommentCount(postId);
          // Immediately reflect filled state on the comment icon for this post
          setCommentIconFilled(postId);
          // Update latest comment display on page
          if (window.updateLatestComment) {
            window.updateLatestComment(postId);
          }
        }
      });
    }
  });

  // Handle reply links
  document.addEventListener('click', function(e) {
    if (e.target.classList.contains('comment-reply-link')) {
      e.preventDefault();
      const commentId = e.target.getAttribute('data-comment-id');
      const replyForm = document.getElementById('reply-form-' + commentId);
      if (replyForm) {
        replyForm.style.display = 'block';
      }
    }
    
    if (e.target.classList.contains('cancel-reply')) {
      const replyForm = e.target.closest('.reply-form');
      if (replyForm) {
        replyForm.style.display = 'none';
      }
    }
  });

  // Handle replies toggle
  document.addEventListener('click', function(e) {
    if (e.target.closest('.replies-toggle')) {
      const toggle = e.target.closest('.replies-toggle');
      const repliesContent = toggle.nextElementSibling;
      
      if (repliesContent.style.display === 'none') {
        repliesContent.style.display = 'block';
        toggle.classList.add('expanded');
      } else {
        repliesContent.style.display = 'none';
        toggle.classList.remove('expanded');
      }
    }
  });

  // Handle reply submission
  document.addEventListener('click', function(e) {
    if (e.target.classList.contains('submit-reply')) {
      e.preventDefault();
      const commentId = e.target.getAttribute('data-comment-id');
      const replyForm = e.target.closest('.reply-form');
      const input = replyForm ? replyForm.querySelector('input[type="text"]') : null;
      const replyText = input ? input.value : '';
      
      if (replyText.trim()) {
        // Get the post ID from the modal
        const modal = document.getElementById('universal-modal');
        const postId = modal ? modal.getAttribute('data-post-id') : null;
        
        if (postId) {
          // Submit the reply via AJAX
          fetch('/wp-admin/admin-ajax.php', {
            method: 'POST',
            body: new URLSearchParams({
              action: 'submit_comment',
              post_id: postId,
              comment: replyText,
              comment_parent: commentId
            })
          })
          .then(response => response.json())
          .then(data => {
            if (data.success) {
              // Hide the reply form
              if (replyForm) {
                replyForm.style.display = 'none';
              }
              // Clear the input
              if (input) {
                input.value = '';
              }
              
              // Insert new reply HTML in the parent comment
              const modalBody = document.querySelector('#universal-modal .modal-body');
              if (modalBody && data.data.comment_html) {
                // Find the parent comment
                const parentComment = modalBody.querySelector(`#comment-${commentId}`);
                if (parentComment) {
                  // Insert the reply inside the parent comment's container
                  const parentContainer = parentComment.closest('.comment-item');
                  if (parentContainer) {
                    parentContainer.insertAdjacentHTML('beforeend', data.data.comment_html);
                  }
                  
                  // Scroll to the new reply
                  setTimeout(() => {
                    const newReply = modalBody.querySelector(`#comment-${data.data.comment_id}`);
                    if (newReply) {
                      newReply.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    }
                  }, 100);
                }
              }
              
              // Update comment count
              updateCommentCount(postId);
              // Immediately reflect filled state on the comment icon for this post
              setCommentIconFilled(postId);
              // Update latest comment display on page
              if (window.updateLatestComment) {
                window.updateLatestComment(postId);
              }
            }
          });
        }
      }
    }
  });

  // THREE-DOT MENU - Toggle menu
  document.body.addEventListener('click', function(e) {
    const optionsBtn = e.target.closest('.comment-options-btn');
    
    if (optionsBtn) {
      e.stopPropagation();
      const menu = optionsBtn.nextElementSibling;
      const isVisible = menu.style.display === 'block';
      
      // Close all other menus first
      document.querySelectorAll('.comment-options-menu').forEach(m => m.style.display = 'none');
      
      // Toggle this menu
      menu.style.display = isVisible ? 'none' : 'block';
    } else {
      // Click outside - close all menus
      if (!e.target.closest('.comment-options-menu')) {
        document.querySelectorAll('.comment-options-menu').forEach(m => m.style.display = 'none');
      }
    }
  });

  // EDIT COMMENT
  document.body.addEventListener('click', function(e) {
    if (e.target.closest('.edit-comment')) {
      const btn = e.target.closest('.edit-comment');
      const commentId = btn.getAttribute('data-comment-id');
      const commentItem = document.querySelector(`[data-comment-id="${commentId}"]`);
      
      if (!commentItem) return;
      
      // Find comment text element (either .comment-text or .reply-text)
      const textEl = commentItem.querySelector('.comment-text') || commentItem.querySelector('.reply-text');
      if (!textEl) return;
      
      const originalText = textEl.getAttribute('data-original-text');
      
      // Close menu
      const menu = btn.closest('.comment-options-menu');
      if (menu) menu.style.display = 'none';
      
      // Replace text with textarea
      const editForm = document.createElement('div');
      editForm.className = 'comment-edit-form';
      editForm.innerHTML = `
        <textarea class="comment-edit-textarea">${originalText}</textarea>
        <div class="comment-edit-actions">
          <button class="comment-edit-save" data-comment-id="${commentId}">Save</button>
          <button class="comment-edit-cancel">Cancel</button>
        </div>
      `;
      
      textEl.style.display = 'none';
      textEl.insertAdjacentElement('afterend', editForm);
      
      // Focus textarea
      const textarea = editForm.querySelector('textarea');
      textarea.focus();
      textarea.setSelectionRange(textarea.value.length, textarea.value.length);
    }
  });

  // SAVE EDIT
  document.body.addEventListener('click', function(e) {
    if (e.target.classList.contains('comment-edit-save')) {
      const btn = e.target;
      const commentId = btn.getAttribute('data-comment-id');
      const form = btn.closest('.comment-edit-form');
      const textarea = form.querySelector('textarea');
      const newContent = textarea.value.trim();
      
      if (!newContent) {
        alert('Comment cannot be empty');
        return;
      }
      
      // Disable button
      btn.disabled = true;
      btn.textContent = 'Saving...';
      
      // Send AJAX request
      fetch('/wp-admin/admin-ajax.php', {
        method: 'POST',
        body: new URLSearchParams({
          action: 'edit_comment',
          comment_id: commentId,
          comment_content: newContent,
          nonce: post_likes_ajax.nonce
        })
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          // Update comment text
          const commentItem = document.querySelector(`[data-comment-id="${commentId}"]`);
          const textEl = commentItem.querySelector('.comment-text') || commentItem.querySelector('.reply-text');
          
          textEl.textContent = data.data.content;
          textEl.setAttribute('data-original-text', newContent);
          textEl.style.display = '';
          
          // Add "Edited" indicator if not already there
          const timeEl = commentItem.querySelector('.comment-time') || commentItem.querySelector('.reply-time');
          if (timeEl && !timeEl.querySelector('.comment-edited')) {
            const editedSpan = document.createElement('span');
            editedSpan.className = 'comment-edited';
            editedSpan.textContent = '(Edited)';
            timeEl.appendChild(editedSpan);
          }
          
          // Remove edit form
          form.remove();
        } else {
          alert('Failed to update comment: ' + (data.data || 'Unknown error'));
          btn.disabled = false;
          btn.textContent = 'Save';
        }
      })
      .catch(error => {
        alert('Error updating comment');
        btn.disabled = false;
        btn.textContent = 'Save';
      });
    }
  });

  // CANCEL EDIT
  document.body.addEventListener('click', function(e) {
    if (e.target.classList.contains('comment-edit-cancel')) {
      const form = e.target.closest('.comment-edit-form');
      const textEl = form.previousElementSibling;
      
      textEl.style.display = '';
      form.remove();
    }
  });

  // DELETE COMMENT
  document.body.addEventListener('click', function(e) {
    if (e.target.closest('.delete-comment')) {
      const btn = e.target.closest('.delete-comment');
      const commentId = btn.getAttribute('data-comment-id');
      const commentItem = document.querySelector(`[data-comment-id="${commentId}"]`);
      
      if (!commentItem) return;
      
      // Close menu
      const menu = btn.closest('.comment-options-menu');
      if (menu) menu.style.display = 'none';
      
      // Find where to insert confirmation (after text)
      const textEl = commentItem.querySelector('.comment-text') || commentItem.querySelector('.reply-text');
      if (!textEl) return;
      
      // Check if confirmation already exists
      if (commentItem.querySelector('.comment-delete-confirm')) return;
      
      // Count replies for confirmation message
      const repliesContainer = commentItem.querySelector('.comment-replies');
      let replyCount = 0;
      let confirmText = 'Delete this comment?';
      
      // Only show reply count for admins (they actually delete replies)
      // Non-admins do soft-delete and replies remain
      if (repliesContainer && comments_modal_data && comments_modal_data.is_admin) {
        const replyItems = repliesContainer.querySelectorAll('.reply-item');
        replyCount = replyItems.length;
        
        if (replyCount > 0) {
          confirmText = `Delete this comment and ${replyCount} ${replyCount === 1 ? 'reply' : 'replies'}?`;
        }
      }
      
      // Create confirmation UI
      const confirmDiv = document.createElement('div');
      confirmDiv.className = 'comment-delete-confirm';
      confirmDiv.innerHTML = `
        <span>${confirmText}</span>
        <button class="comment-delete-no">Cancel</button>
        <button class="comment-delete-yes" data-comment-id="${commentId}">Delete</button>
      `;
      
      textEl.insertAdjacentElement('afterend', confirmDiv);
    }
  });

  // CANCEL DELETE
  document.body.addEventListener('click', function(e) {
    if (e.target.classList.contains('comment-delete-no')) {
      const confirmDiv = e.target.closest('.comment-delete-confirm');
      if (confirmDiv) confirmDiv.remove();
    }
  });

  // CONFIRM DELETE
  document.body.addEventListener('click', function(e) {
    if (e.target.classList.contains('comment-delete-yes')) {
      const btn = e.target;
      const commentId = btn.getAttribute('data-comment-id');
      const confirmDiv = btn.closest('.comment-delete-confirm');
      
      // Disable buttons
      btn.disabled = true;
      btn.textContent = 'Deleting...';
      
      // Send AJAX request
      fetch('/wp-admin/admin-ajax.php', {
        method: 'POST',
        body: new URLSearchParams({
          action: 'delete_comment',
          comment_id: commentId,
          nonce: post_likes_ajax.nonce
        })
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          const postId = data.data.post_id;
          const commentItem = document.querySelector(`[data-comment-id="${commentId}"]`);
          
          if (!commentItem) return;
          
          // SOFT DELETE - Replace content but keep structure
          if (data.data.soft_deleted) {
            // Remove confirmation
            if (confirmDiv) confirmDiv.remove();
            
            // Replace avatar with grey circle
            const avatarEl = commentItem.querySelector('.comment-avatar img');
            if (avatarEl) {
              avatarEl.replaceWith(createGreyCircle());
            }
            
            // Replace username with "---"
            const authorEl = commentItem.querySelector('.comment-author a');
            if (authorEl) {
              authorEl.textContent = '---';
              authorEl.removeAttribute('href');
              authorEl.style.cursor = 'default';
            }
            
            // Replace comment text with italics
            const textEl = commentItem.querySelector('.comment-text') || commentItem.querySelector('.reply-text');
            if (textEl) {
              textEl.innerHTML = '<em>Comment deleted</em>';
            }
            
            // Remove three-dot menu
            const optionsMenu = commentItem.querySelector('.comment-options');
            if (optionsMenu) optionsMenu.remove();
            
            // Count stays the same - no update needed
          } 
          // HARD DELETE - Remove from DOM
          else if (data.data.deleted > 0) {
            commentItem.style.opacity = '0';
            commentItem.style.transition = 'opacity 0.3s ease';
            
            // Also fade out the replies wrapper if it exists (sibling element)
            const repliesWrapper = commentItem.nextElementSibling;
            if (repliesWrapper && repliesWrapper.classList.contains('comment-replies-wrapper')) {
              repliesWrapper.style.opacity = '0';
              repliesWrapper.style.transition = 'opacity 0.3s ease';
            }
            
            setTimeout(() => {
              // Remove parent comment
              commentItem.remove();
              
              // Remove replies wrapper if it exists
              if (repliesWrapper && repliesWrapper.classList.contains('comment-replies-wrapper')) {
                repliesWrapper.remove();
              }
              
              // Update comment count
              if (postId) {
                updateCommentCount(postId);
              }
              
              // Update latest comment display
              if (window.updateLatestComment) {
                window.updateLatestComment(postId);
              }
            }, 300);
          }
        } else {
          alert('Failed to delete comment: ' + (data.data || 'Unknown error'));
          btn.disabled = false;
          btn.textContent = 'Delete';
        }
      })
      .catch(error => {
        alert('Error deleting comment');
        btn.disabled = false;
        btn.textContent = 'Delete';
      });
    }
  });
  
  // Helper function to create grey circle for deleted avatar
  function createGreyCircle() {
    const circle = document.createElement('div');
    circle.className = 'comment-avatar-deleted';
    circle.style.width = '30px';
    circle.style.height = '30px';
    circle.style.borderRadius = '50%';
    circle.style.backgroundColor = '#ccc';
    circle.style.flexShrink = '0';
    return circle;
  }

  // SHARE COMMENT
  document.body.addEventListener('click', function(e) {
    if (e.target.closest('.share-comment')) {
      const btn = e.target.closest('.share-comment');
      const commentId = btn.getAttribute('data-comment-id');
      
      // Build comment URL with hash
      const baseUrl = window.location.origin + window.location.pathname;
      const commentUrl = baseUrl + '#comment-' + commentId;
      
      // Get comment text for title
      const commentItem = document.querySelector(`[data-comment-id="${commentId}"]`);
      const textEl = commentItem ? (commentItem.querySelector('.comment-text') || commentItem.querySelector('.reply-text')) : null;
      const commentText = textEl ? textEl.textContent.substring(0, 100) : 'Comment';
      const shareTitle = 'Comment: ' + commentText + (commentText.length >= 100 ? '...' : '');
      
      // Close menu
      const menu = btn.closest('.comment-options-menu');
      if (menu) menu.style.display = 'none';
      
      // Trigger share modal with comment URL
      if (window.openShareModal) {
        window.openShareModal({
          type: 'comment',
          url: commentUrl,
          title: shareTitle
        });
      } else {
        // Fallback: copy to clipboard
        navigator.clipboard.writeText(commentUrl).then(() => {
          alert('Comment link copied to clipboard!');
        }).catch(() => {
          prompt('Copy this link:', commentUrl);
        });
      }
    }
  });

  // CHECK FOR COMMENT HASH ON PAGE LOAD
  function checkForCommentHash() {
    const hash = window.location.hash;
    console.log('Checking hash:', hash);
    
    if (hash && hash.startsWith('#comment-')) {
      const commentId = hash.replace('#comment-', '');
      console.log('Comment ID detected:', commentId);
      
      // Get post ID from multiple sources
      let postId = null;
      
      // Method 1: Try article with data-post-id
      const article = document.querySelector('article[data-post-id]');
      if (article) {
        postId = article.getAttribute('data-post-id');
        console.log('Post ID from article:', postId);
      }
      
      // Method 2: Try comment icon
      if (!postId) {
        const commentIcon = document.querySelector('.comment-icon[data-post-id]');
        if (commentIcon) {
          postId = commentIcon.getAttribute('data-post-id');
          console.log('Post ID from comment icon:', postId);
        }
      }
      
      // Method 3: Try body class postid-123
      if (!postId) {
        const bodyClasses = document.body.className;
        const match = bodyClasses.match(/postid-(\d+)/);
        if (match) {
          postId = match[1];
          console.log('Post ID from body class:', postId);
        }
      }
      
      // Method 4: Try .wp-block-post with post-123 class
      if (!postId) {
        const wpPost = document.querySelector('.wp-block-post[class*="post-"]');
        if (wpPost) {
          const classMatch = wpPost.className.match(/\bpost-(\d+)\b/);
          if (classMatch) {
            postId = classMatch[1];
            console.log('Post ID from wp-block-post:', postId);
          }
        }
      }
      
      // Method 5: Extract from URL path (last resort)
      if (!postId) {
        // Try to get post by slug from URL and use AJAX
        console.log('Could not detect post ID from DOM, attempting URL-based detection');
      }
      
      if (postId) {
        console.log('Opening comments modal for post:', postId);
        // Open comments modal
        loadCommentsModal(postId);
        
        // After modal opens, scroll to and highlight comment
        setTimeout(() => {
          highlightComment(commentId);
        }, 800);
      } else {
        console.error('Could not detect post ID for comment:', commentId);
      }
    }
  }

  // HIGHLIGHT COMMENT FUNCTION
  function highlightComment(commentId) {
    const commentItem = document.querySelector(`#comment-${commentId}`);
    
    if (!commentItem) return;
    
    // If it's a reply, expand parent comment first
    const parentReply = commentItem.closest('.comment-replies');
    if (parentReply) {
      const repliesContent = parentReply.querySelector('.replies-content');
      const repliesToggle = parentReply.querySelector('.replies-toggle');
      if (repliesContent && repliesToggle) {
        repliesContent.style.display = 'block';
        repliesToggle.classList.add('expanded');
      }
    }
    
    // Scroll to comment
    setTimeout(() => {
      commentItem.scrollIntoView({ behavior: 'smooth', block: 'center' });
      
      // Add highlight class
      commentItem.classList.add('comment-highlighted');
      
      // Remove highlight after 3 seconds
      setTimeout(() => {
        commentItem.classList.remove('comment-highlighted');
      }, 3000);
    }, 300);
  }

  // Run check on page load - try multiple times to ensure DOM is ready
  function initHashCheck() {
    checkForCommentHash();
    // Also check after a delay in case DOM isn't fully loaded
    setTimeout(checkForCommentHash, 100);
    setTimeout(checkForCommentHash, 500);
  }
  
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initHashCheck);
  } else {
    initHashCheck();
  }
  
  // Also check when hash changes (back/forward navigation)
  window.addEventListener('hashchange', checkForCommentHash);
});
