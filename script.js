// MotiVOItion Portfolio - Main JavaScript File

// Dynamic Content Loading
const initSettings = async () => {
    try {
        const isSettings = window.location.pathname.includes('/settings/');
        const isBlog = window.location.pathname.includes('/blog-posts/');

        let path = '';
        if (isSettings) {
            path = 'site-data.json';
        } else if (isBlog) {
            path = '../settings/site-data.json';
        } else {
            path = 'settings/site-data.json';
        }

        const response = await fetch(path);
        if (!response.ok) {
            console.warn('Could not load site-data.json from', path);
            return;
        }

        const data = await response.json();

        // Update Logo Text
        document.querySelectorAll('.logo-motion').forEach(el => el.textContent = data.site_info.logo_text_1);
        document.querySelectorAll('.logo-voition').forEach(el => el.textContent = data.site_info.logo_text_2);

        // Update Hero (index.html)
        const heroTitle = document.getElementById('dynamic-hero-title');
        if (heroTitle && data.hero.title) {
            // Check if we should preserve the typing structure
            const typingText = heroTitle.querySelector('.typing-text');
            if (typingText && data.hero.title.includes('||')) {
                // Support format: Prefix || typing1,typing2 || Suffix
                const parts = data.hero.title.split('||');
                if (parts.length >= 3) {
                    const prefix = heroTitle.querySelector('.title-line:first-child');
                    if (prefix) prefix.textContent = parts[0].trim();

                    const suffix = heroTitle.querySelector('.title-line:last-child');
                    if (suffix) suffix.textContent = parts[2].trim();

                    // The typing words would be in parts[1]
                    // We'll update the global words array if it exists
                    if (typeof window.words !== 'undefined') {
                        window.words = parts[1].split(',').map(s => s.trim());
                    }
                }
            } else {
                heroTitle.textContent = data.hero.title;
            }
        }

        const heroSub = document.getElementById('dynamic-hero-subtitle');
        if (heroSub) heroSub.textContent = data.hero.subtitle;

        const heroCTA = document.getElementById('dynamic-hero-cta');
        if (heroCTA) heroCTA.textContent = data.hero.cta_text;

        // Update About (about.html)
        const aboutTitle = document.getElementById('dynamic-about-title');
        if (aboutTitle) aboutTitle.textContent = data.about.title;

        const aboutSub = document.getElementById('dynamic-about-subtitle');
        if (aboutSub) aboutSub.textContent = data.about.subtitle;

        const aboutDesc = document.getElementById('dynamic-about-description');
        if (aboutDesc) aboutDesc.textContent = data.about.description;

        const aboutPhoto = document.getElementById('dynamic-about-photo');
        if (aboutPhoto && data.about.photo_url) {
            aboutPhoto.src = (isSettings || isBlog) ? '../' + data.about.photo_url : data.about.photo_url;
        }

        // Update Contact
        const contactEmail = document.getElementById('dynamic-contact-email');
        if (contactEmail) {
            contactEmail.textContent = data.contact.email;
            contactEmail.href = `mailto:${data.contact.email}`;
        }

        const contactPhone = document.getElementById('dynamic-contact-phone');
        if (contactPhone) {
            contactPhone.textContent = data.contact.phone;
            contactPhone.href = `tel:${data.contact.phone.replace(/\s/g, '')}`;
        }

        const contactAddress = document.getElementById('dynamic-contact-address');
        if (contactAddress) {
            contactAddress.textContent = data.contact.address;
        }

        // Update Footer
        const footerTagline = document.getElementById('dynamic-footer-tagline');
        if (footerTagline) footerTagline.textContent = data.site_info.tagline;

        // Update Services Page (services.html)
        const srvHeroTitle = document.getElementById('dynamic-services-hero-title');
        if (srvHeroTitle) srvHeroTitle.textContent = data.services.hero_title || 'Premium Motion Video Services';

        const srvHeroSub = document.getElementById('dynamic-services-hero-subtitle');
        if (srvHeroSub) srvHeroSub.textContent = data.services.hero_subtitle || 'Transforming your vision into compelling motion stories.';

        if (data.services && data.services.list) {
            data.services.list.forEach((srv, i) => {
                const title = document.getElementById(`dynamic-service-title-${i}`);
                if (title) title.textContent = srv.title;

                const desc = document.getElementById(`dynamic-service-desc-${i}`);
                if (desc) desc.textContent = srv.description;

                const price = document.getElementById(`dynamic-service-price-${i}`);
                if (price) price.textContent = srv.price;

                const note = document.getElementById(`dynamic-service-price-note-${i}`);
                if (note) note.textContent = srv.price_note;
            });
        }

        if (data.services && data.services.packages) {
            const pkgSubtitle = document.getElementById('dynamic-packages-subtitle');
            if (pkgSubtitle && data.services.packages_subtitle) pkgSubtitle.textContent = data.services.packages_subtitle;

            data.services.packages.forEach((pkg, i) => {
                const name = document.getElementById(`dynamic-package-name-${i}`);
                if (name) name.textContent = pkg.name;

                const price = document.getElementById(`dynamic-package-price-${i}`);
                if (price) price.textContent = pkg.price;

                const period = document.getElementById(`dynamic-package-period-${i}`);
                if (period) period.textContent = pkg.period;

                const featsContainer = document.getElementById(`dynamic-package-features-${i}`);
                if (featsContainer && pkg.features) {
                    featsContainer.innerHTML = pkg.features.map(f => `
                        <div class="package-feature">
                            <i class="fas fa-check"></i>
                            <span>${f}</span>
                        </div>
                    `).join('');
                }
            });
        }

    } catch (error) {
        console.error('Error initializing settings:', error);
    }
};

document.addEventListener('DOMContentLoaded', function () {
    initSettings();
    // ===== MOBILE MENU TOGGLE =====
    const menuToggle = document.getElementById('menuToggle');
    const navMenu = document.querySelector('.main-nav');

    if (menuToggle && navMenu) {
        menuToggle.addEventListener('click', function () {
            navMenu.classList.toggle('active');
            menuToggle.classList.toggle('active');

            // Animate hamburger to X
            const spans = menuToggle.querySelectorAll('.hamburger span');
            if (navMenu.classList.contains('active')) {
                spans[0].style.transform = 'rotate(45deg) translate(5px, 5px)';
                spans[1].style.opacity = '0';
                spans[2].style.transform = 'rotate(-45deg) translate(7px, -6px)';
            } else {
                spans[0].style.transform = 'none';
                spans[1].style.opacity = '1';
                spans[2].style.transform = 'none';
            }
        });

        // Close menu when clicking outside
        document.addEventListener('click', function (event) {
            if (!event.target.closest('.header') && navMenu.classList.contains('active')) {
                navMenu.classList.remove('active');
                menuToggle.classList.remove('active');
                const spans = menuToggle.querySelectorAll('.hamburger span');
                spans[0].style.transform = 'none';
                spans[1].style.opacity = '1';
                spans[2].style.transform = 'none';
            }
        });
    }

    // ===== SMOOTH SCROLL =====
    const navLinks = document.querySelectorAll('a[href^="#"]');
    navLinks.forEach(link => {
        link.addEventListener('click', function (e) {
            if (this.getAttribute('href') === '#') return;

            const targetId = this.getAttribute('href');
            if (targetId.startsWith('#')) {
                e.preventDefault();
                const targetElement = document.querySelector(targetId);
                if (targetElement) {
                    window.scrollTo({
                        top: targetElement.offsetTop - 100,
                        behavior: 'smooth'
                    });

                    // Close mobile menu if open
                    if (navMenu && navMenu.classList.contains('active')) {
                        navMenu.classList.remove('active');
                        menuToggle.classList.remove('active');
                    }
                }
            }
        });
    });

    // ===== ACTIVE NAV LINK ON SCROLL =====
    function setActiveNavLink() {
        const sections = document.querySelectorAll('section[id]');
        const navLinks = document.querySelectorAll('.nav-link');

        let current = '';
        sections.forEach(section => {
            const sectionTop = section.offsetTop;
            const sectionHeight = section.clientHeight;
            if (scrollY >= sectionTop - 200) {
                current = section.getAttribute('id');
            }
        });

        navLinks.forEach(link => {
            link.classList.remove('active');
            if (link.getAttribute('href') === `#${current}`) {
                link.classList.add('active');
            }
        });
    }

    window.addEventListener('scroll', setActiveNavLink);

    // ===== PARALLAX EFFECT =====
    function initParallax() {
        const heroVideo = document.getElementById('heroVideo');
        if (heroVideo) {
            window.addEventListener('scroll', function () {
                const scrolled = window.pageYOffset;
                const rate = scrolled * -0.5;
                heroVideo.style.transform = `translate3d(0px, ${rate}px, 0px)`;
            });
        }
    }

    initParallax();

    // ===== PARTICLES BACKGROUND =====
    function createParticles() {
        const container = document.getElementById('particles');
        if (!container) return;

        const particleCount = 50;

        for (let i = 0; i < particleCount; i++) {
            const particle = document.createElement('div');
            particle.className = 'particle';

            // Random properties
            const size = Math.random() * 4 + 1;
            const posX = Math.random() * 100;
            const posY = Math.random() * 100;
            const duration = Math.random() * 20 + 10;
            const delay = Math.random() * 5;
            const opacity = Math.random() * 0.5 + 0.1;

            // Apply styles
            particle.style.cssText = `
                position: absolute;
                width: ${size}px;
                height: ${size}px;
                background: var(--primary);
                border-radius: 50%;
                left: ${posX}%;
                top: ${posY}%;
                opacity: ${opacity};
                animation: floatParticle ${duration}s linear ${delay}s infinite;
                box-shadow: 0 0 10px var(--primary);
            `;

            container.appendChild(particle);
        }

        // Add CSS for animation
        const style = document.createElement('style');
        style.textContent = `
            @keyframes floatParticle {
                0% {
                    transform: translateY(0) rotate(0deg);
                    opacity: 0;
                }
                10% {
                    opacity: 1;
                }
                90% {
                    opacity: 1;
                }
                100% {
                    transform: translateY(-100vh) rotate(360deg);
                    opacity: 0;
                }
            }
        `;
        document.head.appendChild(style);
    }

    createParticles();

    // ===== VIDEO GRID FUNCTIONALITY =====
    async function initVideoGrid() {
        const videoGrid = document.getElementById('videoGrid');
        if (!videoGrid) return;

        const isSettings = window.location.pathname.includes('/settings/');
        const isBlog = window.location.pathname.includes('/blog-posts/');
        let fetchPath = isSettings ? 'portfolio-handler.php' :
            (isBlog ? '../settings/portfolio-handler.php' : 'settings/portfolio-handler.php');

        try {
            const response = await fetch(fetchPath);
            let videos = [];

            if (response.ok) {
                videos = await response.json();
            }

            // Fallback if no videos in DB or fetch fails
            if (!videos || videos.length === 0) {
                videos = [
                    {
                        id: 1,
                        title: "Urban Motion Capture",
                        description: "Dynamic cityscape videography showcasing modern architecture",
                        src: "assets/videos/showcase-demo.mp4",
                        thumbnail: "assets/videos/thumbnails/thumb1.jpg",
                        date: "2024-03-15",
                        size: "4K",
                        duration: "2:30",
                        category: "motion"
                    }
                ];
            }

            // Clear loading skeletons
            videoGrid.innerHTML = '';

            // Create video cards (limited to 3 for index)
            const displayLimit = document.body.id === 'index-page' ? 3 : videos.length;
            videos.slice(0, displayLimit).forEach(video => {
                const videoCard = createVideoCard(video);
                videoGrid.appendChild(videoCard);
            });
        } catch (error) {
            console.error('Error loading video grid:', error);
        }
    }

    function createVideoCard(video) {
        const card = document.createElement('div');
        card.className = 'portfolio-item';
        card.dataset.category = video.category;

        card.innerHTML = `
            <div class="video-thumbnail">
                <video muted loop playsinline>
                    <source src="${video.src}" type="video/mp4">
                </video>
                <div class="play-overlay">
                    <div class="play-btn">
                        <i class="fas fa-play"></i>
                    </div>
                </div>
            </div>
            <div class="video-info">
                <h3 class="video-title">${video.title}</h3>
                <p class="video-description">${video.description}</p>
                <div class="video-meta">
                    <span class="video-date">
                        <i class="far fa-calendar"></i>
                        ${video.date}
                    </span>
                    <span class="video-size">
                        <i class="fas fa-expand-alt"></i>
                        ${video.size}
                    </span>
                </div>
                <div class="video-actions">
                    <button class="action-btn watch-btn" data-video="${video.id}">
                        <i class="fas fa-eye"></i>
                        Watch
                    </button>
                    <button class="action-btn download-btn" data-video="${video.id}">
                        <i class="fas fa-download"></i>
                        Download
                    </button>
                </div>
            </div>
        `;

        // Add event listeners
        const playBtn = card.querySelector('.play-btn');
        const watchBtn = card.querySelector('.watch-btn');

        playBtn.addEventListener('click', () => openVideoModal(video));
        watchBtn.addEventListener('click', () => openVideoModal(video));

        return card;
    }

    // ===== VIDEO MODAL =====
    function openVideoModal(video) {
        // Create modal if it doesn't exist
        let modal = document.getElementById('videoModal');
        if (!modal) {
            modal = document.createElement('div');
            modal.id = 'videoModal';
            modal.className = 'video-modal';
            modal.innerHTML = `
                <div class="modal-content">
                    <div class="modal-header">
                        <h3 class="modal-title">${video.title}</h3>
                        <button class="close-modal">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <div class="modal-video">
                        <video controls autoplay>
                            <source src="${video.src}" type="video/mp4">
                            Your browser does not support the video tag.
                        </video>
                    </div>
                </div>
            `;
            document.body.appendChild(modal);

            // Add close functionality
            const closeBtn = modal.querySelector('.close-modal');
            closeBtn.addEventListener('click', closeVideoModal);

            // Close on outside click
            modal.addEventListener('click', (e) => {
                if (e.target === modal) {
                    closeVideoModal();
                }
            });

            // Close on Escape key
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape') {
                    closeVideoModal();
                }
            });
        } else {
            // Update modal content
            modal.querySelector('.modal-title').textContent = video.title;
            modal.querySelector('video source').src = video.src;
            modal.querySelector('video').load();
        }

        // Show modal
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }

    function closeVideoModal() {
        const modal = document.getElementById('videoModal');
        if (modal) {
            modal.style.display = 'none';
            document.body.style.overflow = 'auto';

            // Pause video
            const video = modal.querySelector('video');
            video.pause();
        }
    }

    // ===== UPLOAD PAGE FUNCTIONALITY =====
    function initUploadPage() {
        const uploadForm = document.getElementById('uploadForm');
        if (!uploadForm) return;

        const fileInput = document.getElementById('fileInput');
        const uploadArea = document.querySelector('.upload-area');
        const uploadProgress = document.querySelector('.upload-progress');
        const progressFill = document.querySelector('.progress-fill');
        const progressText = document.querySelector('.progress-text');
        const uploadSuccess = document.querySelector('.upload-success');
        const videoPreview = document.querySelector('.video-preview');
        const previewVideo = document.querySelector('.preview-video video');

        // Drag and drop functionality
        if (uploadArea) {
            uploadArea.addEventListener('dragover', (e) => {
                e.preventDefault();
                uploadArea.style.borderColor = 'var(--primary)';
                uploadArea.style.background = 'rgba(0, 243, 255, 0.05)';
            });

            uploadArea.addEventListener('dragleave', () => {
                uploadArea.style.borderColor = 'rgba(0, 243, 255, 0.3)';
                uploadArea.style.background = 'transparent';
            });

            uploadArea.addEventListener('drop', (e) => {
                e.preventDefault();
                uploadArea.style.borderColor = 'rgba(0, 243, 255, 0.3)';
                uploadArea.style.background = 'transparent';

                if (e.dataTransfer.files.length) {
                    fileInput.files = e.dataTransfer.files;
                    handleFileSelect(e.dataTransfer.files[0]);
                }
            });

            uploadArea.addEventListener('click', () => {
                fileInput.click();
            });
        }

        // File input change
        if (fileInput) {
            fileInput.addEventListener('change', (e) => {
                if (e.target.files.length) {
                    handleFileSelect(e.target.files[0]);
                }
            });
        }

        // Handle file selection
        function handleFileSelect(file) {
            if (!file) return;

            // Check file type
            const validTypes = ['video/mp4', 'video/quicktime', 'video/x-m4v'];
            if (!validTypes.includes(file.type)) {
                alert('Please upload MP4 or MOV files only.');
                return;
            }

            // Check file size (max 500MB)
            const maxSize = 500 * 1024 * 1024; // 500MB in bytes
            if (file.size > maxSize) {
                alert('File size must be less than 500MB.');
                return;
            }

            // Show preview
            const reader = new FileReader();
            reader.onload = function (e) {
                if (previewVideo) {
                    previewVideo.src = e.target.result;
                    videoPreview.style.display = 'block';
                }
            };
            reader.readAsDataURL(file);

            // Update file info
            const fileInfo = uploadArea.querySelector('.file-info');
            if (fileInfo) {
                fileInfo.innerHTML = `
                    <strong>Selected File:</strong> ${file.name}<br>
                    <strong>Size:</strong> ${formatFileSize(file.size)}<br>
                    <strong>Type:</strong> ${file.type}
                `;
            }
        }

        // Format file size
        function formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        }

        // Form submission
        if (uploadForm) {
            uploadForm.addEventListener('submit', async (e) => {
                e.preventDefault();

                const formData = new FormData(uploadForm);
                const submitBtn = uploadForm.querySelector('.btn-submit');

                // Disable submit button
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Uploading...';

                // Actual upload using fetch
                try {
                    const response = await fetch('upload-handler.php', {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });

                    const result = await response.json();

                    if (result.success) {
                        // Show success message
                        if (uploadSuccess) {
                            uploadSuccess.style.display = 'block';
                            uploadForm.style.display = 'none';

                            // Update success message with video info if available
                            const successTitle = uploadSuccess.querySelector('h3');
                            if (successTitle) successTitle.textContent = result.message || 'Upload Successful!';
                        }

                        // Reset form after 5 seconds
                        setTimeout(() => {
                            resetUploadUI();
                        }, 5000);
                    } else {
                        throw new Error(result.error || 'Upload failed');
                    }
                } catch (error) {
                    console.error('Upload error:', error);
                    alert('Upload failed: ' + error.message);
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<i class="fas fa-cloud-upload-alt"></i> Upload Video';
                    if (uploadProgress) uploadProgress.style.display = 'none';
                }

                function resetUploadUI() {
                    uploadForm.reset();
                    if (videoPreview) videoPreview.style.display = 'none';
                    if (uploadProgress) uploadProgress.style.display = 'none';
                    if (progressFill) progressFill.style.width = '0%';
                    if (progressText) progressText.textContent = 'Uploading... 0%';
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<i class="fas fa-cloud-upload-alt"></i> Upload Video';

                    if (uploadSuccess) {
                        uploadSuccess.style.display = 'none';
                        uploadForm.style.display = 'block';
                    }
                }
            });
        }

        // GitHub integration
        const githubToggle = document.getElementById('githubToggle');
        const githubFields = document.getElementById('githubFields');

        if (githubToggle && githubFields) {
            githubToggle.addEventListener('change', function () {
                githubFields.style.display = this.checked ? 'grid' : 'none';
            });
        }
    }

    // ===== PORTFOLIO FILTERING =====
    function initPortfolioFilter() {
        const filterBtns = document.querySelectorAll('.filter-btn');
        const portfolioItems = document.querySelectorAll('.portfolio-item');

        if (filterBtns.length && portfolioItems.length) {
            filterBtns.forEach(btn => {
                btn.addEventListener('click', function () {
                    // Remove active class from all buttons
                    filterBtns.forEach(b => b.classList.remove('active'));

                    // Add active class to clicked button
                    this.classList.add('active');

                    const filterValue = this.dataset.filter;

                    // Filter portfolio items
                    portfolioItems.forEach(item => {
                        if (filterValue === 'all' || item.dataset.category === filterValue) {
                            item.style.display = 'block';
                            setTimeout(() => {
                                item.style.opacity = '1';
                                item.style.transform = 'translateY(0)';
                            }, 10);
                        } else {
                            item.style.opacity = '0';
                            item.style.transform = 'translateY(20px)';
                            setTimeout(() => {
                                item.style.display = 'none';
                            }, 300);
                        }
                    });
                });
            });
        }
    }

    // ===== CONTACT FORM =====
    function initContactForm() {
        const contactForm = document.getElementById('contactForm');
        if (!contactForm) return;

        contactForm.addEventListener('submit', async (e) => {
            e.preventDefault();

            const submitBtn = contactForm.querySelector('.btn-submit');
            const originalText = submitBtn.innerHTML;

            // Disable submit button
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending...';

            // Simulate sending (in production, use actual API)
            setTimeout(() => {
                // Show success message
                const successMsg = document.createElement('div');
                successMsg.className = 'success-message';
                successMsg.innerHTML = `
                    <div class="success-icon">
                        <i class="fas fa-check"></i>
                    </div>
                    <h3>Message Sent!</h3>
                    <p>Thank you for contacting us. We'll get back to you soon.</p>
                `;

                contactForm.innerHTML = '';
                contactForm.appendChild(successMsg);

                // Reset form after 5 seconds
                setTimeout(() => {
                    contactForm.innerHTML = `
                        <div class="form-group">
                            <label class="form-label" for="name">
                                <i class="fas fa-user"></i> Full Name
                            </label>
                            <input type="text" id="name" name="name" class="form-input" required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="email">
                                <i class="fas fa-envelope"></i> Email Address
                            </label>
                            <input type="email" id="email" name="email" class="form-input" required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="subject">
                                <i class="fas fa-tag"></i> Subject
                            </label>
                            <input type="text" id="subject" name="subject" class="form-input" required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="message">
                                <i class="fas fa-comment"></i> Message
                            </label>
                            <textarea id="message" name="message" class="form-textarea" rows="5" required></textarea>
                        </div>
                        
                        <button type="submit" class="btn-submit">
                            <i class="fas fa-paper-plane"></i> Send Message
                        </button>
                    `;

                    // Reinitialize the form
                    initContactForm();
                }, 5000);
            }, 2000);
        });
    }

    // ===== BLOG FUNCTIONALITY =====
    async function initBlog() {
        const blogGrid = document.getElementById('blogGrid');
        if (!blogGrid) return;

        const blogSearch = document.getElementById('blogSearch');
        const categoryItems = document.querySelectorAll('.category-item');
        const tagLinks = document.querySelectorAll('.tag-link');
        const newsletterForm = document.getElementById('newsletterForm');
        let allPostsData = [];

        function renderPosts(postsToRender) {
            blogGrid.innerHTML = '';
            if (!postsToRender || postsToRender.length === 0) {
                blogGrid.innerHTML = '<div class="no-results"><h2>No blog posts found matching your criteria.</h2></div>';
                return;
            }

            postsToRender.forEach((blog, index) => {
                const article = document.createElement('article');
                article.className = `blog-post ${index === 0 ? 'featured-post' : ''}`;
                article.innerHTML = `
                    <img src="${blog.image_url}" alt="${blog.title}" class="post-image">
                    <div class="post-content">
                        <div class="post-meta">
                            <span class="post-category">${blog.category}</span>
                            <span class="post-date"><i class="far fa-calendar"></i> ${blog.date}</span>
                            <span class="read-time"><i class="far fa-clock"></i> ${blog.read_time || '5 min read'}</span>
                        </div>
                        <h2 class="post-title">${blog.title}</h2>
                        <p class="post-excerpt">${blog.excerpt || ''}</p>
                        <div class="post-tags">
                            ${(blog.tags || []).map(tag => `<span class="post-tag">#${tag}</span>`).join('')}
                        </div>
                        <div class="post-footer">
                            <div class="post-author">
                                <img src="${blog.author_avatar || 'assets/images/logo.svg'}" alt="${blog.author}" class="author-avatar">
                                <div class="author-info">
                                    <h4>${blog.author || 'MotiVOItion'}</h4>
                                    <p>${blog.author_role || 'Creative Director'}</p>
                                </div>
                            </div>
                            <a href="post.html?id=${blog.id}" class="read-more">
                                Read ${index === 0 ? 'Full Article' : 'More'}
                                <i class="fas fa-arrow-right"></i>
                            </a>
                        </div>
                    </div>
                `;
                blogGrid.appendChild(article);
            });
        }

        try {
            const isSettings = window.location.pathname.includes('/settings/');
            const isBlogPost = window.location.pathname.includes('/blog-posts/');
            let path = isSettings ? 'blogs.json' :
                (isBlogPost ? '../settings/blogs.json' : 'settings/blogs.json');

            const response = await fetch(path);
            let blogs = [];
            if (response.ok) {
                blogs = await response.json();
            }

            if (blogs && blogs.length > 0) {
                blogs.sort((a, b) => new Date(b.date) - new Date(a.date));
                allPostsData = blogs;
                renderPosts(allPostsData);
            } else {
                // Fallback for demo if no real posts
                renderPosts([]);
            }
        } catch (error) {
            console.error('Error loading blogs:', error);
            blogGrid.innerHTML = '<div class="no-results"><h2>Error loading blog posts</h2></div>';
        }

        if (blogSearch) {
            blogSearch.addEventListener('input', function () {
                const searchTerm = this.value.toLowerCase();
                const filtered = allPostsData.filter(post =>
                    post.title.toLowerCase().includes(searchTerm) ||
                    (post.excerpt || '').toLowerCase().includes(searchTerm) ||
                    (post.tags || []).some(t => t.toLowerCase().includes(searchTerm))
                );
                renderPosts(filtered);
            });
        }

        if (categoryItems.length > 0) {
            categoryItems.forEach(item => {
                item.addEventListener('click', function (e) {
                    e.preventDefault();
                    categoryItems.forEach(i => i.classList.remove('active'));
                    this.classList.add('active');
                    const category = this.dataset.category;
                    if (category === 'all') {
                        renderPosts(allPostsData);
                    } else {
                        renderPosts(allPostsData.filter(p => p.category.toLowerCase() === category.toLowerCase()));
                    }
                });
            });
        }
    }

    // ===== LAZY LOAD IMAGES AND VIDEOS =====
    function initLazyLoading() {
        const lazyMedia = document.querySelectorAll('[data-src], [data-srcset]');

        if ('IntersectionObserver' in window) {
            const lazyObserver = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const media = entry.target;

                        if (media.dataset.src) {
                            media.src = media.dataset.src;
                        }

                        if (media.dataset.srcset) {
                            media.srcset = media.dataset.srcset;
                        }

                        media.classList.remove('lazy');
                        lazyObserver.unobserve(media);
                    }
                });
            });

            lazyMedia.forEach(media => lazyObserver.observe(media));
        }
    }

    // ===== INITIALIZE ALL FUNCTIONALITIES =====
    initVideoGrid();
    initUploadPage();
    initPortfolioFilter();
    initContactForm();
    initBlog();
    initLazyLoading();

    // ===== PERFORMANCE OPTIMIZATION =====
    // Debounce scroll events
    let scrollTimeout;
    window.addEventListener('scroll', () => {
        clearTimeout(scrollTimeout);
        scrollTimeout = setTimeout(setActiveNavLink, 100);
    });

    // Preload critical resources
    function preloadResources() {
        const criticalImages = [
            'assets/images/logo.svg',
            'assets/videos/hero-background.mp4'
        ];

        criticalImages.forEach(src => {
            const link = document.createElement('link');
            link.rel = 'preload';
            link.as = src.endsWith('.mp4') ? 'video' : 'image';
            link.href = src;
            document.head.appendChild(link);
        });
    }

    preloadResources();

    // ===== ERROR HANDLING =====
    window.addEventListener('error', function (e) {
        console.error('Error occurred:', e.error);
        // You could send this to an error tracking service
    });

    // ===== SERVICE WORKER FOR OFFLINE SUPPORT =====
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', function () {
            navigator.serviceWorker.register('/sw.js').then(function (registration) {
                console.log('ServiceWorker registration successful');
            }, function (err) {
                console.log('ServiceWorker registration failed: ', err);
            });
        });
    }

    // ===== ANALYTICS (Optional) =====
    // You can integrate Google Analytics or any other analytics service here
    /*
    window.dataLayer = window.dataLayer || [];
    function gtag(){dataLayer.push(arguments);}
    gtag('js', new Date());
    gtag('config', 'YOUR_GA_ID');
    */
});

// ===== UTILITY FUNCTIONS =====
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

function throttle(func, limit) {
    let inThrottle;
    return function () {
        const args = arguments;
        const context = this;
        if (!inThrottle) {
            func.apply(context, args);
            inThrottle = true;
            setTimeout(() => inThrottle = false, limit);
        }
    };
}

// ===== EXPORT FOR MODULES =====
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {
        debounce,
        throttle
    };
}