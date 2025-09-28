<?php

/*
 * Image Lightbox by WPCookie
 * https://redpishi.com/wordpress-tutorials/lightbox-wordpress/
 * */
add_action( 'wp_footer', function(){ if(!is_admin()) { ?>

<script>
window.addEventListener('load', function () {
  // Only enable lightbox on screens wider than 768px
  if (window.innerWidth <= 1024) {
    return; // Do nothing for mobiles/small screens
  }

  // Select all candidate images to attach open handlers
  const allImages = [...document.querySelectorAll('.gallery1hr img')];
  allImages.forEach(e => e.style.cursor = "zoom-in");

  // Current group scoped to clicked post/gallery
  let currentGroup = [];
  let currentIndex = 0;

  // Attach lightbox opener
  allImages.forEach(item => item.addEventListener('click', handleCreateLightbox));

  function handleCreateLightbox(e) {
    const linkImage = e.target.getAttribute('src');
    // Build scoped group: prefer closest .gallery1hr; fallback to nearest post wrapper
    const container = e.target.closest('.gallery1hr') || e.target.closest('.wp-block-post') || document;
    currentGroup = [...container.querySelectorAll('.gallery1hr img')];
    if (!currentGroup.length) { currentGroup = [e.target]; }
    currentIndex = Math.max(0, currentGroup.findIndex(img => img.getAttribute('src') === linkImage));
    const code = `
      <div class="lightbox">
        <div class="lightbox-content">
          <i class="lightbox-prev">‹</i>
          <img src="${linkImage}" alt="" class="lightbox-image" />
          <i class="lightbox-next">›</i>
        </div>
      </div>`;
    document.body.insertAdjacentHTML('beforeend', code);
  }

  let index = 0; // deprecated, kept for minimal refactor; we will use currentIndex
  document.addEventListener('click', handleOutLightbox);

  function handleOutLightbox(e) {
    const lightImage = document.querySelector('.lightbox-image');
    if (!lightImage) return;

    let imageSrc = '';
    if (e.target.matches('.lightbox')) {
      e.target.remove();

    } else if (e.target.matches('.lightbox-next')) {
      imageSrc = lightImage.getAttribute('src');
      currentIndex = currentGroup.findIndex(item => item.getAttribute('src') === imageSrc) + 1;
      if (currentIndex > currentGroup.length - 1) currentIndex = 0;
      ChangeLinkImage(currentIndex, lightImage);

    } else if (e.target.matches('.lightbox-prev')) {
      imageSrc = lightImage.getAttribute('src');
      currentIndex = currentGroup.findIndex(item => item.getAttribute('src') === imageSrc) - 1;
      if (currentIndex < 0) currentIndex = currentGroup.length - 1;
      ChangeLinkImage(currentIndex, lightImage);
    }
  }

  function ChangeLinkImage(index, lightImage) {
    const list = currentGroup.length ? currentGroup : allImages;
    const newLink = list[index].getAttribute('src');
    lightImage.setAttribute('src', newLink);
  }
});
</script>
<?php } } );