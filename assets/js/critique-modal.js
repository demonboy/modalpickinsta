/* Critique Modal - Standalone modal for critique comments */
(function(){
  if (typeof window === 'undefined') return;

  var modal, dialog, backdrop, closeBtn, title, textarea, counter, submitBtn, currentPostId, existingCommentId;

  function createEl(tag, cls){
    var el = document.createElement(tag);
    if(cls){ el.className = cls; }
    return el;
  }

  function ensureModal(){
    if (modal) return;
    
    modal = createEl('div', 'critique-modal');
    backdrop = createEl('div', 'critique-backdrop');
    dialog = createEl('div', 'critique-dialog');
    
    var header = createEl('div', 'critique-header');
    title = createEl('h3', 'critique-title');
    title.textContent = 'Leave a Critique';
    closeBtn = createEl('button', 'critique-close');
    closeBtn.setAttribute('aria-label', 'Close');
    closeBtn.innerHTML = '&times;';
    header.appendChild(title);
    header.appendChild(closeBtn);
    
    var form = createEl('div', 'critique-form');
    textarea = createEl('textarea', 'critique-textarea');
    textarea.setAttribute('maxlength', '5000');
    
    counter = createEl('div', 'critique-counter');
    counter.textContent = '0/100';
    
    submitBtn = createEl('button', 'critique-submit');
    submitBtn.textContent = 'Submit Critique';
    submitBtn.disabled = true;
    
    form.appendChild(textarea);
    form.appendChild(counter);
    form.appendChild(submitBtn);
    
    dialog.appendChild(header);
    dialog.appendChild(form);
    modal.appendChild(backdrop);
    modal.appendChild(dialog);
    document.body.appendChild(modal);
    
    // Events
    backdrop.addEventListener('click', close);
    closeBtn.addEventListener('click', close);
    document.addEventListener('keydown', function(e){
      if(e.key === 'Escape' && modal.classList.contains('is-open')) close();
    });
    
    textarea.addEventListener('input', updateCounter);
    submitBtn.addEventListener('click', handleSubmit);
  }

  function updateCounter(){
    var length = textarea.value.length;
    var minChars = 100;
    
    counter.textContent = length + '/100';
    
    if (length < minChars) {
      counter.classList.remove('is-valid');
      counter.classList.add('is-invalid');
      submitBtn.disabled = true;
    } else {
      counter.classList.remove('is-invalid');
      counter.classList.add('is-valid');
      submitBtn.disabled = false;
    }
    
    // Show max warning at 4500+
    if (length > 4500) {
      counter.textContent = length + '/5000';
    }
  }

  function open(postId){
    ensureModal();
    currentPostId = postId;
    existingCommentId = null;
    
    // Reset form
    textarea.value = '';
    updateCounter();
    submitBtn.disabled = false;
    submitBtn.textContent = 'Submit Critique';
    
    // Check if user already has a critique on this post
    fetch('/wp-admin/admin-ajax.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded',
      },
      body: new URLSearchParams({
        action: 'get_user_critique',
        post_id: postId,
        nonce: post_likes_ajax.nonce
      })
    })
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        // Set placeholder with author's name
        var authorName = data.data.author_name || 'the photographer';
        textarea.setAttribute('placeholder', 'Use this form to submit your constructive criticism. This differs from a standard comment in that you are offering useful feedback on the set of three photographs to help ' + authorName + ' improve their skills.');
        
        if (data.data.has_critique) {
          // Pre-fill with existing critique
          title.textContent = 'Edit Your Critique';
          textarea.value = data.data.content;
          existingCommentId = data.data.comment_id;
          updateCounter();
        } else {
          title.textContent = 'Leave a Critique';
        }
      }
    })
    .catch(error => {
      console.error('Error fetching critique:', error);
    });
    
    modal.classList.add('is-open');
    textarea.focus();
  }

  function close(){
    if(modal){
      modal.classList.remove('is-open');
    }
  }

  function handleSubmit(e){
    e.preventDefault();
    
    var content = textarea.value.trim();
    
    if (content.length < 100) {
      alert('Critique must be at least 100 characters.');
      return;
    }
    
    if (content.length > 5000) {
      alert('Critique cannot exceed 5000 characters.');
      return;
    }
    
    submitBtn.disabled = true;
    submitBtn.textContent = 'Submitting...';
    
    var formData = {
      action: 'submit_critique',
      post_id: currentPostId,
      critique: content,
      nonce: post_likes_ajax.nonce
    };
    
    if (existingCommentId) {
      formData.comment_id = existingCommentId;
    }
    
    fetch('/wp-admin/admin-ajax.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded',
      },
      body: new URLSearchParams(formData)
    })
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        close();
        
        // Open or refresh comments modal with the new critique
        if (window.loadCommentsModal) {
          // Always load/refresh the comments modal to show the critique
          window.loadCommentsModal(currentPostId);
          
          // If this was a new critique, scroll to it after a brief delay
          if (data.data.is_new && data.data.comment_id) {
            setTimeout(function() {
              var commentEl = document.getElementById('comment-' + data.data.comment_id);
              if (commentEl) {
                commentEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
              }
            }, 500);
          }
        }
      } else {
        alert('Error: ' + (data.data || 'Failed to submit critique'));
        submitBtn.disabled = false;
        submitBtn.textContent = 'Submit Critique';
      }
    })
    .catch(error => {
      console.error('Error submitting critique:', error);
      alert('An error occurred. Please try again.');
      submitBtn.disabled = false;
      submitBtn.textContent = 'Submit Critique';
    });
  }

  // Listen for clicks on critique-welcome buttons
  document.addEventListener('click', function(e){
    var btn = e.target.closest('.critique-welcome-btn');
    if (!btn) return;
    
    e.preventDefault();
    e.stopPropagation();
    
    var postId = btn.getAttribute('data-post-id');
    if (postId) {
      open(postId);
    }
  });

  // Expose open function globally
  window.openCritiqueModal = function(postId){
    open(postId);
  };
})();

