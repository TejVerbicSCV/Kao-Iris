<?php
$currentYear = date('Y');
?>
<footer class="footer-bottom">
    <p>&copy; <?php echo $currentYear; ?> Portal IRIS. Vse pravice pridržane.</p>
</footer>

<style>
.footer {
    background-color: #2c3e50;
    color: white;
    padding: 2rem 0;
    margin-top: 3rem;
}

.footer-content {
    max-width: 1200px;
    margin: 0 auto;
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 2rem;
    padding: 0 1rem;
}

.footer-section h3 {
    color: #3498db;
    margin-bottom: 1rem;
}

.footer-section ul {
    list-style: none;
    padding: 0;
}

.footer-section ul li {
    margin-bottom: 0.5rem;
}

.footer-section a {
    color: white;
    text-decoration: none;
    transition: color 0.3s;
}

.footer-section a:hover {
    color: #3498db;
}

.footer-bottom {
    text-align: center;
    margin-top: 0;
    padding-top: 1rem;
    
    background-color: #2c3e50;
    color: white;
    padding: 1rem 0;
}

@media (max-width: 768px) {
    .footer-content {
        grid-template-columns: 1fr;
        text-align: center;
    }
}
</style> 