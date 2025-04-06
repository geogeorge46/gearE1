// Wait for the DOM to be fully loaded
document.addEventListener('DOMContentLoaded', function() {
    // Navbar scroll effect
    const navbar = document.querySelector('.navbar');
    window.addEventListener('scroll', function() {
        if (window.scrollY > 50) {
            navbar.style.background = 'rgba(255, 255, 255, 0.98)';
            navbar.style.boxShadow = '0 2px 15px rgba(0, 0, 0, 0.1)';
        } else {
            navbar.style.background = 'rgba(255, 255, 255, 0.95)';
            navbar.style.boxShadow = '0 2px 10px rgba(0, 0, 0, 0.1)';
        }
    });

    // Search functionality
    const searchInput = document.querySelector('.search-bar input');
    const searchBtn = document.querySelector('.search-btn');
    const locationSelect = document.querySelector('.location-select');

    searchBtn.addEventListener('click', function() {
        const searchTerm = searchInput.value.trim();
        const location = locationSelect.value;
        if (searchTerm) {
            // You can implement actual search functionality here
            console.log(`Searching for: ${searchTerm} in ${location}`);
            // Example alert for demonstration
            alert(`Searching for ${searchTerm} in ${location}`);
        }
    });

    // Machine Cards Animation on Scroll
    const machineCards = document.querySelectorAll('.machine-card');
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };

    const observer = new IntersectionObserver(function(entries) {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0)';
            }
        });
    }, observerOptions);

    machineCards.forEach(card => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(50px)';
        observer.observe(card);
    });

    // Rent Now Button Click Handler
    const rentButtons = document.querySelectorAll('.rent-btn');
    rentButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            const machineCard = e.target.closest('.machine-card');
            const machineName = machineCard.querySelector('h3').textContent;
            const price = machineCard.querySelector('.price').textContent;
            
            // Show rental confirmation modal (you can customize this)
            showRentalModal(machineName, price);
        });
    });

    // Rental Modal Function
    function showRentalModal(machineName, price) {
        const modal = document.createElement('div');
        modal.className = 'rental-modal';
        modal.innerHTML = `
            <div class="modal-content">
                <h3>Rent ${machineName}</h3>
                <p>Price: ${price}</p>
                <div class="rental-details">
                    <input type="date" id="rental-date" min="${new Date().toISOString().split('T')[0]}">
                    <select id="rental-duration">
                        <option value="1">1 Day</option>
                        <option value="3">3 Days</option>
                        <option value="7">7 Days</option>
                        <option value="30">30 Days</option>
                    </select>
                </div>
                <div class="modal-buttons">
                    <button class="confirm-btn">Confirm Rental</button>
                    <button class="cancel-btn">Cancel</button>
                </div>
            </div>
        `;

        document.body.appendChild(modal);

        // Modal event listeners
        modal.querySelector('.cancel-btn').addEventListener('click', () => {
            modal.remove();
        });

        modal.querySelector('.confirm-btn').addEventListener('click', () => {
            const date = modal.querySelector('#rental-date').value;
            const duration = modal.querySelector('#rental-duration').value;
            // Handle rental confirmation
            alert(`Rental confirmed for ${machineName}\nDate: ${date}\nDuration: ${duration} days`);
            modal.remove();
        });
    }

    // Category Cards Hover Effect
    const categoryCards = document.querySelectorAll('.category-card');
    categoryCards.forEach(card => {
        card.addEventListener('mousemove', function(e) {
            const rect = card.getBoundingClientRect();
            const x = e.clientX - rect.left;
            const y = e.clientY - rect.top;

            card.style.transform = `
                perspective(1000px)
                rotateX(${(y - rect.height/2) / 20}deg)
                rotateY(${-(x - rect.width/2) / 20}deg)
                translateZ(10px)
            `;
        });

        card.addEventListener('mouseleave', function() {
            card.style.transform = 'none';
        });
    });

    // Testimonials Slider
    let currentTestimonial = 0;
    const testimonials = document.querySelectorAll('.testimonial-card');
    
    function showNextTestimonial() {
        testimonials[currentTestimonial].style.opacity = '0';
        currentTestimonial = (currentTestimonial + 1) % testimonials.length;
        testimonials[currentTestimonial].style.opacity = '1';
    }

    // Change testimonial every 5 seconds
    setInterval(showNextTestimonial, 5000);

    // Add smooth scrolling for navigation links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });

    // Add modal styles dynamically
    const style = document.createElement('style');
    style.textContent = `
        .rental-modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }

        .modal-content {
            background: white;
            padding: 2rem;
            border-radius: 15px;
            max-width: 500px;
            width: 90%;
        }

        .rental-details {
            margin: 1rem 0;
            display: flex;
            gap: 1rem;
        }

        .modal-buttons {
            display: flex;
            gap: 1rem;
            margin-top: 1rem;
        }

        .confirm-btn, .cancel-btn {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .confirm-btn {
            background: var(--primary);
            color: white;
        }

        .cancel-btn {
            background: #e5e7eb;
        }
    `;
    document.head.appendChild(style);
});