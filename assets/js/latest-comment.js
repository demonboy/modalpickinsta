document.addEventListener('DOMContentLoaded', function() {
  // Listen for modal close events
  document.addEventListener('universal-modal-close', function() {
    // Get the modal element to check if it was a comments modal
    const modal = document.getElementById('universal-modal');
    if (!modal) return;
    
    // Get the post ID from the modal if it exists
    const postId = modal.getAttribute('data-post-id');
    if (!postId) return;
    
    // Update the latest comment for this post
    updateLatestComment(postId);
  });
  
  // Also listen for modal closing via backdrop or ESC key
  const modalElement = document.getElementById('universal-modal');
  if (modalElement) {
    const backdrop = modalElement.querySelector('.modal-backdrop');
    if (backdrop) {
      backdrop.addEventListener('click', function() {
        const postId = modalElement.getAttribute('data-post-id');
        if (postId) {
          setTimeout(function() {
            updateLatestComment(postId);
          }, 300); // Small delay to ensure modal is fully closed
        }
      });
    }
  }
  
  // Make latest comment clickable to open comments modal
  document.body.addEventListener('click', function(e) {
    const latestComment = e.target.closest('.latest-comment-display');
    if (latestComment) {
      const postId = latestComment.getAttribute('data-post-id');
      if (postId && window.loadCommentsModal) {
        window.loadCommentsModal(postId);
      }
    }
  });
  
  // Function to update latest comment
  function updateLatestComment(postId) {
    // Fetch latest comment via AJAX
    fetch('/wp-admin/admin-ajax.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded',
      },
      body: new URLSearchParams({
        action: 'get_latest_comment',
        post_id: postId,
        nonce: post_likes_ajax.nonce
      })
    })
    .then(function(response) {
      return response.json();
    })
    .then(function(data) {
      if (data.success) {
        // Find the specific latest comment display for this post
        const existingDisplay = document.querySelector('.latest-comment-display[data-post-id="' + postId + '"]');
        
        if (existingDisplay) {
          // Found existing display for this post - update it
          const container = existingDisplay.closest('.latest-comment');
          const column = container ? container.querySelector('.wp-block-column') : null;
          
          if (column) {
            if (data.data.has_comment) {
              // Update the comment
              column.innerHTML = data.data.html;
              
              // Hide the "Add a comment" block for this post
              hideAddCommentBlock(postId);
            } else {
              // No comments, hide container and show "Add a comment" block
              column.innerHTML = '';
              showAddCommentBlock(postId);
            }
          }
        }
      }
    })
    .catch(function(error) {
      console.log('Error updating latest comment:', error);
    });
  }
  
  // Hide "Add a comment" block when latest comment exists
  function hideAddCommentBlock(postId) {
    const addCommentBlocks = document.querySelectorAll('.comtrigfield[data-post-id="' + postId + '"]');
    addCommentBlocks.forEach(function(block) {
      const parentColumns = block.closest('.wp-block-columns.are-vertically-aligned-center');
      if (parentColumns && !parentColumns.classList.contains('latest-comment')) {
        parentColumns.style.display = 'none';
      }
    });
  }
  
  // Show "Add a comment" block when no comments exist
  function showAddCommentBlock(postId) {
    const addCommentBlocks = document.querySelectorAll('.comtrigfield[data-post-id="' + postId + '"]');
    addCommentBlocks.forEach(function(block) {
      const parentColumns = block.closest('.wp-block-columns.are-vertically-aligned-center');
      if (parentColumns && !parentColumns.classList.contains('latest-comment')) {
        parentColumns.style.display = '';
      }
    });
  }
  
  // On page load, hide "Add a comment" blocks where latest comments exist
  document.querySelectorAll('.latest-comment').forEach(function(latestCommentContainer) {
    // Check if this container has a latest-comment-display inside
    const display = latestCommentContainer.querySelector('.latest-comment-display');
    if (display) {
      const postId = display.getAttribute('data-post-id');
      if (postId) {
        hideAddCommentBlock(postId);
      }
    }
  });
});