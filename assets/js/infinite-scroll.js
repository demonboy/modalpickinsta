/**
 * Infinite Scroll functionality - Complete rewrite
 * Works with custom postpic-feed structure
 */

(function() {
    'use strict';

    // Wait for DOM to be ready
    function init() {
        const feedContainer = document.querySelector('.postpic-feed[data-infinite-scroll="true"]');
        
        if (!feedContainer) {
            return; // No infinite scroll container found
        }

        const maxPages = parseInt(feedContainer.getAttribute('data-max-pages'));
        if (maxPages <= 1) {
            return; // No pagination needed
        }

        let currentPage = 1;
        let isLoading = false;
        let hasMorePosts = true;

        // Create loading indicator
        function createLoadingIndicator() {
            const loadingDiv = document.createElement('div');
            loadingDiv.className = 'infinite-scroll-loading';
            loadingDiv.innerHTML = `
                <div class="loading-spinner"></div>
                <p>Loading more posts...</p>
            `;
            return loadingDiv;
        }

        // Create end indicator
        function createEndIndicator() {
            const endDiv = document.createElement('div');
            endDiv.className = 'infinite-scroll-end';
            endDiv.innerHTML = `<p>You've reached the end of the content.</p>`;
            return endDiv;
        }

        // Load more posts
        function loadMorePosts() {
            if (isLoading || !hasMorePosts) {
                return;
            }

            isLoading = true;
            const nextPage = currentPage + 1;

            // Show loading indicator
            const loadingIndicator = createLoadingIndicator();
            feedContainer.appendChild(loadingIndicator);

            // Prepare AJAX data
            const formData = new FormData();
            formData.append('action', 'infinite_scroll');
            formData.append('page', nextPage);
            formData.append('nonce', ajax_object.nonce);

            // Add context-specific data
            if (window.postpicQueryInfo) {
                formData.append('post_type', window.postpicQueryInfo.postTypes.join(','));
                formData.append('posts_per_page', window.postpicQueryInfo.postsPerPage);
                formData.append('query_type', window.postpicQueryInfo.queryType || 'home');
                
                if (window.postpicQueryInfo.authorId) {
                    formData.append('author_id', window.postpicQueryInfo.authorId);
                }
                if (window.postpicQueryInfo.category) {
                    formData.append('category', window.postpicQueryInfo.category);
                }
                if (window.postpicQueryInfo.tag) {
                    formData.append('tag', window.postpicQueryInfo.tag);
                }
                if (window.postpicQueryInfo.postTypeArchive) {
                    formData.append('post_type_archive', window.postpicQueryInfo.postTypeArchive);
                }
                if (window.postpicQueryInfo.searchQuery) {
                    formData.append('search_query', window.postpicQueryInfo.searchQuery);
                }
            }

            // Make AJAX request
            fetch(ajax_object.ajax_url, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                // Remove loading indicator
                const loadingEl = feedContainer.querySelector('.infinite-scroll-loading');
                if (loadingEl) {
                    loadingEl.remove();
                }

                if (data.success && data.data.posts_html) {
                    // Append new posts
                    feedContainer.insertAdjacentHTML('beforeend', data.data.posts_html);
                    
                    // Update page counter
                    currentPage = data.data.next_page;
                    hasMorePosts = data.data.has_more;
                    feedContainer.setAttribute('data-page', currentPage);

                    // Re-initialize post interactions
                    initializePostInteractions();

                    // If no more posts, show end indicator
                    if (!hasMorePosts) {
                        const endIndicator = createEndIndicator();
                        feedContainer.appendChild(endIndicator);
                    }
                } else {
                    // Handle error
                    console.error('Infinite scroll error:', data);
                    hasMorePosts = false;
                }
            })
            .catch(error => {
                console.error('Infinite scroll fetch error:', error);
                
                // Remove loading indicator
                const loadingEl = feedContainer.querySelector('.infinite-scroll-loading');
                if (loadingEl) {
                    loadingEl.remove();
                }
                
                hasMorePosts = false;
            })
            .finally(() => {
                isLoading = false;
            });
        }

        // Initialize post interactions for newly loaded content
        function initializePostInteractions() {
            // Re-initialize like buttons
            const likeButtons = feedContainer.querySelectorAll('.feed-like-btn:not([data-initialized])');
            likeButtons.forEach(button => {
                button.setAttribute('data-initialized', 'true');
                button.addEventListener('click', function(e) {
                    e.preventDefault();
                    const postId = this.getAttribute('data-post-id');
                    // Trigger existing like functionality
                    if (window.postLikesHandler) {
                        window.postLikesHandler(this, postId);
                    }
                });
            });

            // Re-initialize comment buttons
            const commentButtons = feedContainer.querySelectorAll('.feed-comments-btn:not([data-initialized])');
            commentButtons.forEach(button => {
                button.setAttribute('data-initialized', 'true');
                button.addEventListener('click', function(e) {
                    e.preventDefault();
                    const postId = this.getAttribute('data-post-id');
                    // Trigger existing comment modal functionality
                    if (window.openCommentsModal) {
                        window.openCommentsModal(postId);
                    }
                });
            });

            // Re-initialize share buttons
            const shareButtons = feedContainer.querySelectorAll('.feed-share-btn:not([data-initialized])');
            shareButtons.forEach(button => {
                button.setAttribute('data-initialized', 'true');
                button.addEventListener('click', function(e) {
                    e.preventDefault();
                    const postId = this.getAttribute('data-post-id');
                    // Trigger existing share functionality
                    if (window.sharePost) {
                        window.sharePost(postId);
                    }
                });
            });
        }

        // Initialize intersection observer
        function initIntersectionObserver() {
            if (!('IntersectionObserver' in window)) {
                return; // Fallback not needed for this implementation
            }

            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting && hasMorePosts && !isLoading) {
                        loadMorePosts();
                    }
                });
            }, {
                root: null,
                rootMargin: '100px',
                threshold: 0.1
            });

            // Create sentinel element
            const sentinel = document.createElement('div');
            sentinel.className = 'infinite-scroll-sentinel';
            sentinel.setAttribute('aria-hidden', 'true');
            
            feedContainer.appendChild(sentinel);
            observer.observe(sentinel);
        }

        // Initialize everything
        initIntersectionObserver();
        initializePostInteractions();
    }

    // Start when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

})();