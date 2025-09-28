document.addEventListener('DOMContentLoaded', function() {
  console.log('Post likes JS loaded!');
  
  // POST LIKES
  document.addEventListener('click', function(e) {
    console.log('Click detected on:', e.target);
    const likeButton = e.target.closest('.like-button');
    if (likeButton) {
      console.log('Like button found!');
      e.preventDefault();
      
      const postId = likeButton.dataset.postId;
      const commentId = likeButton.dataset.commentId || null;
      
      console.log('Post ID:', postId, 'Comment ID:', commentId);
      
      fetch(post_likes_ajax.ajax_url, {
        method: 'POST',
        body: new URLSearchParams({
          action: 'toggle_like',
          post_id: postId,
          comment_id: commentId,
          nonce: post_likes_ajax.nonce
        })
      })
      .then(response => response.json())
      .then(data => {
        console.log('Response:', data);
        console.log('Liked:', data.data.liked);
        console.log('Count:', data.data.count);
        if (data.success) {
          likeButton.classList.toggle('liked', data.data.liked);
          const countSpan = likeButton.querySelector('.like-count');
          console.log('Count span:', countSpan);
          if (countSpan) {
            console.log('Setting count to:', data.data.count);
            countSpan.textContent = String(data.data.count || 0);
            if ((data.data.count || 0) === 0) { countSpan.classList.add('is-zero'); }
            else { countSpan.classList.remove('is-zero'); }
          } else {
            console.log('Count span not found!');
          }
        }
      });
    }
  });
});