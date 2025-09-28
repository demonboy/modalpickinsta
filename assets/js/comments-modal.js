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
          const note = (data && data.data && data.data.wants_cf) ? `<p>${dn} is looking for constructive feedback on their photography. Leave your thoughts below</p>` : '';
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

  // Update comment count function
  function updateCommentCount(postId) {
    // Find all comment icons for this post
    const commentIcons = document.querySelectorAll(`[data-post-id="${postId}"]`);
    
    commentIcons.forEach(icon => {
      const countSpan = icon.querySelector('.comment-count');
      if (countSpan) {
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
              countSpan.textContent = data.data.count;
            }
          })
          .catch(error => {
            console.log('Error updating comment count:', error);
          });
      }
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
            }
          });
        }
      }
    }
  });
});
