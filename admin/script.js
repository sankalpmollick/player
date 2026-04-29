document.addEventListener('DOMContentLoaded', function() {

    // Find the success message element on the page
    const successMessage = document.querySelector('.success');

    // If the success message exists...
    if (successMessage) {
        // ...wait for 3 seconds (3000 milliseconds)
        setTimeout(() => {
            // After 3 seconds, start fading it out
            successMessage.style.opacity = '0';
            
            // After the fade-out animation is complete (500ms), hide it completely
            // so it doesn't take up any space.
            setTimeout(() => {
                successMessage.style.display = 'none';
            }, 500);

        }, 3000);
    }

});
