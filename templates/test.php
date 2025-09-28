<?php
/**
 * Template Name: Test Template
 * 
 * This is a custom template for testing the PostPic layout structure
 */

get_header(); ?>

<style>
    /* Basic body styling for better presentation */
    body {
        font-family: sans-serif;
        background-color: #f4f4f4;
        color: #333;
        margin: 0;
        padding: 20px;
    }

    /* Main container for the content */
    .content-wrapper {
        max-width: 1200px; /* Sets the maximum width of the content area */
        margin: 0 auto; /* Centers the content on the page */
    }

    h1, p {
        text-align: center;
    }

    /* 
     * 1. The Flex Container for the Row
     */
    .image-row {
        /* Core Flexbox setup */
        display: flex;

        /* Aligns children: pushes first/last to edges, distributes space in between */
        justify-content: space-between;

        /* Vertically aligns images to the center of the row */
        align-items: center;

        /* Creates a flexible gap between the images */
        gap: 20px; /* You can adjust this value */

        /* Sizing and Responsiveness */
        width: 100%;
        height: 300px; /* The target height for the row */

        /* Adds vertical spacing between rows */
        margin-bottom: 20px;

        /* Optional: adds a border for visualization */
        border: 2px dashed #ccc;
        padding: 10px;
        box-sizing: border-box;
    }

    /* 
     * 2. Styling for the Images within the Row
     */
    .image-row img {
        /* Sizing and Aspect Ratio Preservation */
        display: block;
        height: auto; /* Allows height to adjust based on width to maintain ratio */
        width: auto;   /* Allows width to adjust based on height to maintain ratio */
        max-height: 100%; /* CRITICAL: Ensures image never exceeds the container's height */
        max-width: 100%;  /* Ensures image doesn't overflow its flexible container space */

        /* Prevents images from growing but allows them to shrink if space is tight */
        flex-grow: 0;
        flex-shrink: 1;
    }
	.icons {
		padding-bottom:50px;
	}
</style>

<div class="content-wrapper">
    <h1>Responsive Image Row Demo</h1>
    <p>Each row below is a flex container. The images maintain their aspect ratio while being constrained by the row's height.</p>

    <!-- Row 1: A mix of portrait, landscape, and standard images -->
    <div class="image-row">
        <img src="/testpics/share.jpg" alt="A tall portrait placeholder image">
        <img src="/testpics/alignavat.jpg" alt="A wide landscape placeholder image">
        <img src="/testpics/info.jpg" alt="A standard placeholder image">
    </div>
    <div class="icons">Here are our icons</div>
    
    <!-- Row 2: Different shapes and sizes -->
    <div class="image-row">
        <img src="/testpics/s-l1600.png" alt="A wide landscape placeholder image">
        <img src="/testpics/houspng.png" alt="A square placeholder image">
        <img src="/testpics/options.jpg" alt="A tall portrait placeholder image">
    </div>
    <div class="icons">Here are our icons</div>
</div>

<?php get_footer(); ?>

