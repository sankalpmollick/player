document.addEventListener('DOMContentLoaded', function() {
    
    // ==========================================
    // 1. AUTO-HIDE SUCCESS/ERROR MESSAGES
    // ==========================================
    const alertMessages = document.querySelectorAll('.success, .error');
    
    alertMessages.forEach(msg => {
        // 4 seconds (4000ms) tak message dikhega, fir gayab ho jayega
        setTimeout(() => {
            msg.style.transition = 'opacity 0.5s ease, margin 0.5s ease, padding 0.5s ease';
            msg.style.opacity = '0';
            
            // Pura element hatane ke liye
            setTimeout(() => {
                msg.style.display = 'none';
                msg.remove();
            }, 500);
        }, 4000);
    });

    // ==========================================
    // 2. CATEGORY SELECTION UX
    // ==========================================
    // Agar user naya category type kar raha hai, toh dropdown ko dim kar do
    const newCategoryInput = document.querySelector('input[name="new_category"]');
    const existingCategorySelect = document.querySelector('select[name="existing_category"]');

    if (newCategoryInput && existingCategorySelect) {
        newCategoryInput.addEventListener('input', function() {
            if (this.value.trim() !== '') {
                // Naya text likha hai, toh dropdown ko fade kar do
                existingCategorySelect.style.opacity = '0.4';
                existingCategorySelect.title = "New category will override this selection";
            } else {
                // Text box khali hai, toh dropdown ko wapas normal kar do
                existingCategorySelect.style.opacity = '1';
                existingCategorySelect.title = "";
            }
        });
    }

    // ==========================================
    // 3. FILE SIZE WARNINGS (Client-Side)
    // ==========================================
    const audioFileInputs = document.querySelectorAll('input[name="audio_file"], input[name="new_audio_file"]');
    const imageFileInputs = document.querySelectorAll('input[name="thumbnail_file"], input[name="new_thumbnail_file"]');

    // Audio file check (Max 100MB warning)
    audioFileInputs.forEach(input => {
        input.addEventListener('change', function() {
            const file = this.files[0];
            const maxSize = 100 * 1024 * 1024; // 100 MB in bytes
            
            if (file && file.size > maxSize) {
                alert('⚠️ Warning: Your audio file is larger than 100MB. It might take a long time to upload depending on your internet speed.');
            }
        });
    });

    // Image file check (Max 5MB warning)
    imageFileInputs.forEach(input => {
        input.addEventListener('change', function() {
            const file = this.files[0];
            const maxSize = 5 * 1024 * 1024; // 5 MB in bytes
            
            if (file && file.size > maxSize) {
                alert('⚠️ Warning: Your thumbnail image is larger than 5MB. Large images make the website load slower. Consider compressing it.');
            }
        });
    });

    // ==========================================
    // 4. CONFIRMATION FOR DELETE ACTION
    // ==========================================
    // (Vaise HTML me onclick return confirm lagaya hai, par safety ke liye double check)
    const deleteButtons = document.querySelectorAll('.btn-delete');
    deleteButtons.forEach(btn => {
        btn.addEventListener('click', function(e) {
            // Agar HTML ka inline onclick kaam na kare toh ye backup hai
            if(!confirm('Are you sure you want to permanently delete this track?')) {
                e.preventDefault();
            }
        });
    });

});
