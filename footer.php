<?php
/**
 * Reusable footer with:
 * - Full-width background color
 * - Content strictly 70% width
 * - Button-style social icons
 */
?>
<style>
/* Footer structure */
.footer-background {
    background-color: #0f2e24; /* Full-width background */
    width: 100%;
    margin-top: 2rem;
}

.footer-content {
    width: 70%; /* Content width constraint */
    margin: 0 auto; /* Center the content */
    padding: 2rem 0;
    color: white;
}

/* Grid layout */
.footer-grid {
    display: flex;
    flex-wrap: wrap;
    justify-content: space-between;
    gap: 30px;
}

.footersections {
    flex: 1;
    min-width: 250px; /* Minimum width before wrapping */
}

/* Visual elements */
.footer-title {
    font-size: 1.2rem;
    margin-bottom: 1rem;
    color: #4CAF50;
}

.social-buttons {
    display: flex;
    gap: 12px;
    margin-top: 1rem;
}

.social-btn {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: rgba(255,255,255,0.1);
    color: white;
    border: none;
    font-size: 1.2rem;
    cursor: pointer;
    transition: all 0.3s;
}

.social-btn:hover {
    background: #4CAF50;
    transform: translateY(-3px);
}

.copyright {
    text-align: center;
    margin-top: 2rem;
    padding-top: 1rem;
    border-top: 1px solid rgba(255,255,255,0.1);
    font-size: 0.9rem;
}
</style>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
<div class="footer-background">
    <div class="footer-content">
        <div class="footer-grid">
            <!-- Contact Section -->
            <div class="footersections">
                <h3 class="footer-title">Contact</h3>
                
                    <p><i class="bi bi-geo-alt"></i> United City, Madani Ave, Dhaka-1212</p>
                    <p><i class="bi bi-telephone"></i> 01234567899, 01234567898</p>
                    <p><i class="bi bi-clock"></i> Open everyday except Fridays</p>
            </div>
            
            <!-- Social Media Section -->
            <div class="footersections">
                <h3 class="footer-title">Follow Us</h3>
                <div class="social-buttons">
                    <button class="social-btn"><i class="bi bi-facebook"></i></button>
                    <button class="social-btn"><i class="bi bi-instagram"></i></button>
                    <button class="social-btn"><i class="bi bi-whatsapp"></i></button>
                    <button class="social-btn"><i class="bi bi-youtube"></i></button>
                </div>
            </div>
            
            <!-- Quick Links Section -->
            <div class="footersections">
                <h3 class="footer-title">Our Location</h3>
                    <a href="https://maps.app.goo.gl/v9cTxTCEnY5oyy1Y9" target="_blank" class="map-link">
                        <i class="bi bi-map"></i> View on Google Maps
                    </a>
            </div>
        </div>
        
        <!-- Copyright -->
        <div class="copyright">
            &copy; <?= date('Y') ?> E-Shongskar. All rights reserved.
        </div>
    </div>
</div>