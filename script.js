document.addEventListener('DOMContentLoaded', () => {
    // --- DOM Elements ---
    const audio = document.getElementById('audio');
    const playPauseBtn = document.getElementById('play-pause-btn');
    const progressBarContainer = document.querySelector('.progress-bar-container');
    const progressBar = document.getElementById('progress-bar');
    const currentTimeDisplay = document.getElementById('current-time');
    const totalDurationDisplay = document.getElementById('total-duration');
    const audioTitleEl = document.getElementById('audio-title');
    
    const listContainer = document.getElementById('audio-list');
    const paginationContainer = document.getElementById('pagination-container');
    const searchInput = document.getElementById('searchInput');
    
    const categoryIcon = document.getElementById('category-icon');
    const menuIcon = document.getElementById('menu-icon');
    const categorySidebar = document.getElementById('category-sidebar');
    const sidebarMenu = document.getElementById('sidebar-menu');
    const categoryList = document.getElementById('category-list');

    // --- State Variables ---
    let allAudioData = [];
    let filteredData = [];
    let currentPage = 1;
    const itemsPerPage = 6; // Ek page par 6 cards
    let currentPlayingAudioUrl = null;
    let currentPlayingId = null;

    // ==========================================
    // 1. SIDEBAR LOGIC
    // ==========================================
    function setupSidebars() {
        categoryIcon.addEventListener('click', (e) => {
            categorySidebar.classList.toggle('sidebar-open');
            sidebarMenu.classList.remove('sidebar-open');
            e.stopPropagation();
        });

        menuIcon.addEventListener('click', (e) => {
            sidebarMenu.classList.toggle('sidebar-open');
            categorySidebar.classList.remove('sidebar-open');
            e.stopPropagation();
        });

        document.addEventListener('click', (e) => {
            if (!categorySidebar.contains(e.target) && !categoryIcon.contains(e.target)) {
                categorySidebar.classList.remove('sidebar-open');
            }
            if (!sidebarMenu.contains(e.target) && !menuIcon.contains(e.target)) {
                sidebarMenu.classList.remove('sidebar-open');
            }
        });
    }

    // ==========================================
    // 2. FETCH DATA & INITIALIZE
    // ==========================================
    fetch('admin/index.php?action=get_all_audio')
        .then(res => res.json())
        .then(data => {
            allAudioData = data;
            filteredData = data;
            
            renderCategories(data);
            displayPage(filteredData, 1);
            
            const urlParams = new URLSearchParams(window.location.search);
            const trackId = urlParams.get('track_id');
            
            if (trackId) {
                const trackToPlay = allAudioData.find(t => t.id === trackId);
                if (trackToPlay) {
                    loadAndPlay(trackToPlay.audioUrl, trackToPlay.title, trackToPlay.id);
                }
            } else if (allAudioData.length > 0) {
                const first = allAudioData[0];
                audio.src = first.audioUrl;
                audioTitleEl.textContent = first.title;
                currentPlayingAudioUrl = first.audioUrl;
                currentPlayingId = first.id;
                updateShareLinks(first.id, first.title);
            }
        })
        .catch(err => console.error("Failed to load audio data:", err));

    setupSidebars();

    // ==========================================
    // 3. CATEGORY SYSTEM
    // ==========================================
    function renderCategories(data) {
        const categories = ['All', ...new Set(data.map(item => item.category || 'General'))];
        categoryList.innerHTML = '';
        
        categories.forEach(cat => {
            const li = document.createElement('li');
            const a = document.createElement('a');
            a.href = "#";
            a.innerHTML = `${cat}`;
            if (cat === 'All') a.classList.add('active');
            
            a.addEventListener('click', (e) => {
                e.preventDefault();
                document.querySelectorAll('#category-list a').forEach(el => el.classList.remove('active'));
                a.classList.add('active');
                
                if (cat === 'All') {
                    filteredData = allAudioData;
                } else {
                    filteredData = allAudioData.filter(item => (item.category || 'General') === cat);
                }
                
                searchInput.value = ''; 
                displayPage(filteredData, 1);
                categorySidebar.classList.remove('sidebar-open');
            });
            
            li.appendChild(a);
            categoryList.appendChild(li);
        });
    }

    // ==========================================
    // 4. DISPLAY CARDS & PAGINATION
    // ==========================================
    function displayPage(items, page) {
        currentPage = page;
        listContainer.innerHTML = '';
        
        const startIndex = (page - 1) * itemsPerPage;
        const endIndex = startIndex + itemsPerPage;
        const paginatedItems = items.slice(startIndex, endIndex);
        
        if (paginatedItems.length === 0) {
            listContainer.innerHTML = '<p style="grid-column: 1/-1; text-align: center; color: #777;">No tracks found.</p>';
            paginationContainer.innerHTML = '';
            return;
        }
        
        paginatedItems.forEach(item => {
            const card = document.createElement('div');
            card.className = 'audio-card';
            card.innerHTML = `
                <img src="${item.thumbnailUrl}" alt="Thumbnail">
                <p>${item.title}</p>
                <small><i class="fas fa-tag"></i> ${item.category || 'General'}</small>
                <button class="play-btn" data-url="${item.audioUrl}" data-title="${item.title}" data-id="${item.id}">
                    <i class="fas fa-play"></i> Play
                </button>
            `;
            listContainer.appendChild(card);
        });
        
        setupPagination(items);
    }

    function setupPagination(items) {
        paginationContainer.innerHTML = '';
        const pageCount = Math.ceil(items.length / itemsPerPage);
        if (pageCount <= 1) return;
        
        // Prev Button
        const prevBtn = document.createElement('a');
        prevBtn.className = 'pagination-btn' + (currentPage === 1 ? ' disabled' : '');
        prevBtn.innerHTML = '<i class="fas fa-chevron-left"></i> Prev';
        prevBtn.addEventListener('click', (e) => {
            e.preventDefault();
            if (currentPage > 1) {
                displayPage(items, currentPage - 1);
                window.scrollTo({ top: document.querySelector('.search-container').offsetTop - 20, behavior: 'smooth' });
            }
        });
        paginationContainer.appendChild(prevBtn);

        // Page Numbers
        for (let i = 1; i <= pageCount; i++) {
            const btn = document.createElement('a');
            btn.className = 'pagination-btn' + (i === currentPage ? ' active' : '');
            btn.innerText = i;
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                displayPage(items, i);
                window.scrollTo({ top: document.querySelector('.search-container').offsetTop - 20, behavior: 'smooth' });
            });
            paginationContainer.appendChild(btn);
        }

        // Next Button
        const nextBtn = document.createElement('a');
        nextBtn.className = 'pagination-btn' + (currentPage === pageCount ? ' disabled' : '');
        nextBtn.innerHTML = 'Next <i class="fas fa-chevron-right"></i>';
        nextBtn.addEventListener('click', (e) => {
            e.preventDefault();
            if (currentPage < pageCount) {
                displayPage(items, currentPage + 1);
                window.scrollTo({ top: document.querySelector('.search-container').offsetTop - 20, behavior: 'smooth' });
            }
        });
        paginationContainer.appendChild(nextBtn);
    }

    // ==========================================
    // 5. SEARCH LOGIC (Title & Category)
    // ==========================================
    searchInput.addEventListener('input', (e) => {
        const term = e.target.value.toLowerCase();
        
        const activeCatEl = document.querySelector('#category-list a.active');
        const activeCat = activeCatEl ? activeCatEl.innerText : 'All';
        
        const searched = allAudioData.filter(item => {
            const matchTitle = item.title.toLowerCase().includes(term);
            const matchCategory = (item.category || 'General').toLowerCase().includes(term);
            return matchTitle || matchCategory;
        });
        
        if (activeCat !== 'All') {
            filteredData = searched.filter(item => (item.category || 'General') === activeCat);
        } else {
            filteredData = searched;
        }
        
        displayPage(filteredData, 1);
    });

    // ==========================================
    // 6. PLAYER CONTROLS & LOGIC
    // ==========================================
    listContainer.addEventListener('click', (e) => {
        const btn = e.target.closest('.play-btn');
        if (btn) {
            loadAndPlay(btn.dataset.url, btn.dataset.title, btn.dataset.id);
        }
    });

    function loadAndPlay(url, title, id) {
        if (currentPlayingAudioUrl === url && !audio.paused) {
            audio.pause();
            return;
        }
        if (currentPlayingAudioUrl !== url) {
            audio.src = url;
            currentPlayingAudioUrl = url;
            currentPlayingId = id;
            audioTitleEl.textContent = title;
            
            history.pushState(null, '', `?track_id=${id}`);
            updateShareLinks(id, title);
        }
        audio.play();
    }

    function formatTime(seconds) {
        if (isNaN(seconds)) return "0:00";
        const m = Math.floor(seconds / 60);
        const s = Math.floor(seconds % 60);
        return `${m}:${s < 10 ? '0' : ''}${s}`;
    }
    
    playPauseBtn.addEventListener('click', () => {
        if (!audio.src || audio.src.endsWith(window.location.host + '/')) return;
        if (audio.paused) audio.play();
        else audio.pause();
    });
    
    audio.addEventListener('play', () => { playPauseBtn.innerHTML = '<i class="fas fa-pause"></i>'; });
    audio.addEventListener('pause', () => { playPauseBtn.innerHTML = '<i class="fas fa-play"></i>'; });
    
    audio.addEventListener('timeupdate', () => {
        const progress = (audio.currentTime / audio.duration) * 100;
        progressBar.style.width = `${progress || 0}%`;
        currentTimeDisplay.textContent = formatTime(audio.currentTime);
    });
    
    audio.addEventListener('loadedmetadata', () => {
        totalDurationDisplay.textContent = formatTime(audio.duration);
    });
    
    progressBarContainer.addEventListener('click', (e) => {
        if (!audio.src || audio.src.endsWith(window.location.host + '/')) return;
        const width = progressBarContainer.clientWidth;
        const clickX = e.offsetX;
        audio.currentTime = (clickX / width) * audio.duration;
    });

    // --- AUTO NEXT PLAY LOGIC ---
    audio.addEventListener('ended', () => {
        const currentIndex = filteredData.findIndex(track => track.id === currentPlayingId);

        if (currentIndex !== -1 && currentIndex + 1 < filteredData.length) {
            const nextTrack = filteredData[currentIndex + 1];

            const nextPage = Math.floor((currentIndex + 1) / itemsPerPage) + 1;
            if (nextPage !== currentPage) {
                displayPage(filteredData, nextPage);
            }

            loadAndPlay(nextTrack.audioUrl, nextTrack.title, nextTrack.id);
        } else {
            playPauseBtn.innerHTML = '<i class="fas fa-play"></i>';
            progressBar.style.width = '0%';
            currentTimeDisplay.textContent = '0:00';
        }
    });

    // ==========================================
    // 7. SHARE & DOWNLOAD
    // ==========================================
    function updateShareLinks(id, title) {
        const shareUrl = `${window.location.origin}${window.location.pathname}?track_id=${id}`;
        const encodedUrl = encodeURIComponent(shareUrl);
        const encodedTitle = encodeURIComponent(`Listen to "${title}" on Thetrue Player`);
        
        document.querySelector('.whatsapp-btn').href = `https://api.whatsapp.com/send?text=${encodedTitle} %0A${encodedUrl}`;
        document.querySelector('.facebook-btn').href = `https://www.facebook.com/sharer/sharer.php?u=${encodedUrl}`;
        document.querySelector('.twitter-btn').href = `https://twitter.com/intent/tweet?text=${encodedTitle}&url=${encodedUrl}`;
    }

    document.getElementById('share-btn').addEventListener('click', () => {
        if (!currentPlayingId) { alert("Please play a track first to share!"); return; }
        const shareUrl = `${window.location.origin}${window.location.pathname}?track_id=${currentPlayingId}`;
        navigator.clipboard.writeText(shareUrl).then(() => {
            alert("Link copied to clipboard! You can paste it anywhere.");
        });
    });

    document.getElementById('download-btn').addEventListener('click', () => {
        if (!currentPlayingAudioUrl) { alert("Please play a track first to download!"); return; }
        
        const link = document.createElement('a');
        link.href = currentPlayingAudioUrl;
        
        const ext = currentPlayingAudioUrl.split('.').pop();
        link.download = `${audioTitleEl.textContent}.${ext}`;
        
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    });
});
