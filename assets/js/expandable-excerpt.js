document.addEventListener('DOMContentLoaded', () => {
    document.body.addEventListener('click', (e) => {
        const jexcerpt = e.target.closest('.jexcerpt');
        if (!jexcerpt) return;
        
        // Find the post container (works in query loop and single posts)
        const postContainer = jexcerpt.closest('article, .post, [class*="type-"]');
        
        // Check if this is a 1hrphoto post
        const is1hrphoto = postContainer && (
            postContainer.classList.contains('type-1hrphoto') || 
            postContainer.classList.contains('post-type-1hrphoto') ||
            postContainer.classList.contains('single-1hrphoto')
        );
        
        if (is1hrphoto) {
            // 1hrphoto: expand/collapse on click
            e.preventDefault();
            jexcerpt.classList.toggle('expanded');
        } else {
            // Story posts: navigate to the story post
            e.preventDefault();
            if (postContainer) {
                // Try to find the post permalink link
                const postLink = postContainer.querySelector('.wp-block-post-title a, .post-title a, a[rel="bookmark"]');
                if (postLink && postLink.href) {
                    window.location.href = postLink.href;
                }
            }
        }
    });
});

