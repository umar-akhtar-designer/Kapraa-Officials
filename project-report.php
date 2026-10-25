<?php
include 'include/head.php';
include 'include/navbar.php';
?>

<div class="page section-header text-center mb-0">
    <div class="page-title">
        <div class="wrapper"><h1 class="page-width">Final Year Project Report</h1></div>
    </div>
</div>

<div class="bredcrumbWrap bredcrumbWrapPage bredcrumb-style2 text-center mb-0">
    <div class="container breadcrumbs">
        <a href="<?php echo $url_domain; ?>/index" title="Back to the home page">Home</a>
        <span aria-hidden="true">|</span>
        <span class="title-bold">Project Report</span>
    </div>
</div>

<div class="container py-4 py-md-5 project-report-page">
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-4 p-md-5">
            <p class="text-uppercase text-muted mb-2">BSCS Final Year Project</p>
            <h2 class="mb-3">AI Based Smart Fashion Recommendation System for Clothing E-Commerce</h2>
            <p class="mb-1"><strong>By</strong></p>
            <p class="mb-1">Umar Akhtar - 2022-GCUF-082993</p>
            <p class="mb-4">Nouman Waheed - 2022-GCUF-082994</p>

            <p class="mb-1">Project submitted in partial fulfillment of the requirements for the Degree of</p>
            <p class="mb-3"><strong>Bachelor of Science in Computer Science</strong></p>
            <p class="mb-1">Department of Computer Science</p>
            <p class="mb-1">Government College University, Faisalabad</p>
            <p class="mb-0">February 2026</p>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-4 p-md-5">
            <h3 class="mb-3">Dedication</h3>
            <p>
                This project is dedicated to our beloved parents, whose love, prayers, and guidance have always
                been our greatest source of strength. Their encouragement has inspired us to strive for
                excellence in all that we do.
            </p>
            <p class="mb-0"><strong>Umar Akhtar</strong><br><strong>Nouman Waheed</strong></p>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-4 p-md-5">
            <h3 class="mb-3">Declaration</h3>
            <p>
                We hereby declare that the content of the project, "AI Based Smart Fashion Recommendation System
                for Clothing E-commerce", is the product of our own research and no part has been copied from
                any published source (except references and standard models/equations/protocols).
            </p>
            <p>
                We further declare that this work has not been submitted for the award of any other diploma or
                degree. The university can take action if the above statement is found inaccurate at any stage.
            </p>
            <div class="row mt-4">
                <div class="col-md-6 mb-3 mb-md-0">
                    <p class="mb-1"><strong>Name:</strong> Umar Akhtar</p>
                    <p class="mb-0"><strong>Registration No:</strong> 2022-GCUF-082993</p>
                </div>
                <div class="col-md-6">
                    <p class="mb-1"><strong>Name:</strong> Nouman Waheed</p>
                    <p class="mb-0"><strong>Registration No:</strong> 2022-GCUF-082994</p>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-4 p-md-5">
            <h3 class="mb-3">Certificate By Supervisory Committee</h3>
            <p>
                This is to certify that the project report entitled "AI Based Smart Fashion Recommendation System for
                Clothing E-commerce", submitted by Umar Akhtar (2022-GCUF-082993) and Nouman Waheed
                (2022-GCUF-082994), has been examined as part of the examination for the award of the degree of
                Bachelor of Science in Computer Science from Government College University Faisalabad, Pakistan.
            </p>
            <p class="mb-1">Supervisor: ______________________________</p>
            <p class="mb-1">Member I: ______________________________</p>
            <p class="mb-1">Member II: ______________________________</p>
            <p class="mb-0">Chairperson: ______________________________</p>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-4 p-md-5">
            <h3 class="mb-3">Table of Contents</h3>
            <ol class="mb-0">
                <li>Introduction</li>
                <li>Problem Statement</li>
                <li>Literature Review</li>
                <li>System Overview</li>
                <li>System Design</li>
                <li>System Intelligence</li>
                <li>Implementation</li>
                <li>Implementation Plan</li>
                <li>User Interface Design</li>
                <li>Security Features</li>
                <li>Testing Methodology</li>
                <li>Results / Expected Outcomes</li>
                <li>Future Enhancements</li>
                <li>Conclusion</li>
            </ol>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-4 p-md-5">
            <h3 class="mb-3">Chapter 1: Introduction</h3>
            <p>
                Online shopping has changed how people buy clothes in Pakistan. Every day, thousands of
                people visit clothing websites looking for dresses for weddings, Eid, office wear, or casual
                outings. However, they face a common problem: too many options and no personal help.
            </p>
            <p>
                This project addresses these challenges by creating an intelligent fashion recommendation
                system. Instead of using only filters and categories, users can chat with the system or use
                voice commands. The AI understands natural language, cultural context, and occasion
                requirements to provide personalized recommendations.
            </p>
            <p class="mb-0">
                Through an admin panel, business owners can manage products, set prices, control discounts,
                and monitor sales. For wholesale buyers, the system offers automatic bulk discounts and
                set-suit suggestions.
            </p>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-4 p-md-5">
            <h3 class="mb-3">Chapter 2: Problem Statement</h3>
            <ul class="mb-0">
                <li>Too many products create decision paralysis for customers.</li>
                <li>Existing platforms do not understand Pakistani cultural differences.</li>
                <li>Occasion-based guidance (wedding, Eid, office, casual) is missing.</li>
                <li>Wholesale and bulk purchasing workflows are inefficient.</li>
                <li>Users cannot ask context-based fashion questions naturally.</li>
                <li>Business owners have limited control over recommendation behavior.</li>
            </ul>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-4 p-md-5">
            <h3 class="mb-3">Chapter 3: Literature Review</h3>
            <p class="mb-2"><strong>Current platforms analysis:</strong></p>
            <ul>
                <li>Daraz.pk - filtering available, but no Pakistani fashion AI intelligence.</li>
                <li>Brand websites - products displayed, but no personal recommendation assistant.</li>
                <li>International platforms - AI exists, but lacks Pakistani cultural context.</li>
                <li>Local boutique sites - mostly basic websites without smart features.</li>
            </ul>
            <p class="mb-0">
                <strong>Gap identified:</strong> No platform combines AI + Pakistani cultural intelligence +
                occasion awareness + wholesale support in one practical system.
            </p>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-4 p-md-5">
            <h3 class="mb-3">Chapter 4: System Overview</h3>
            <h5>Who Will Use Our System</h5>
            <ul>
                <li><strong>Regular customers:</strong> personalized help for events and daily wear.</li>
                <li><strong>Wholesale buyers:</strong> bulk ordering with automatic discounts.</li>
                <li><strong>Business owners:</strong> product/order management and report monitoring.</li>
            </ul>
            <h5>What Makes Us Different</h5>
            <ul class="mb-0">
                <li>Natural AI chat instead of only manual filtering.</li>
                <li>Cultural recommendation awareness for different Pakistani regions.</li>
                <li>Occasion-specific guidance with explainable suggestions.</li>
                <li>Voice input for accessibility and convenience.</li>
                <li>Bulk purchase intelligence for wholesale use cases.</li>
            </ul>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-4 p-md-5">
            <h3 class="mb-3">Chapter 5: System Design</h3>
            <h5>5.1 Technologies We Use</h5>
            <ul>
                <li>PHP for frontend rendering and backend business logic.</li>
                <li>MySQL for user, product, order, and AI-rule storage.</li>
                <li>HTML/CSS/JavaScript + Bootstrap for responsive UI.</li>
                <li>Voice API integration for speech-to-text support.</li>
                <li>HTTPS, password hashing, input validation, and backups for security.</li>
            </ul>
            <h5>5.2 Database Design</h5>
            <ul class="mb-0">
                <li>Users table</li>
                <li>Products table</li>
                <li>Orders table</li>
                <li>AI Rules table</li>
                <li>Categories table</li>
            </ul>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-4 p-md-5">
            <h3 class="mb-3">Chapter 6: System Intelligence</h3>
            <ul class="mb-0">
                <li>Understands natural language queries from text and voice.</li>
                <li>Applies Pakistani cultural context in recommendations.</li>
                <li>Considers occasion, weather, budget, and user behavior.</li>
                <li>Provides explainable suggestions ("why this outfit fits your need").</li>
                <li>Supports business-side intelligence for stock and trend handling.</li>
            </ul>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-4 p-md-5">
            <h3 class="mb-3">Chapter 7: Implementation</h3>
            <ul class="mb-0">
                <li><strong>Frontend:</strong> user interface and interactive shopping journey.</li>
                <li><strong>Backend:</strong> PHP modules for users, products, AI, and orders.</li>
                <li><strong>Database:</strong> persistent storage for catalog, profiles, and transactions.</li>
                <li><strong>Security:</strong> secure sessions, protected requests, and safe data flow.</li>
                <li><strong>Scalability:</strong> modular architecture for future extension.</li>
            </ul>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-4 p-md-5">
            <h3 class="mb-3">Chapter 8: Implementation Plan</h3>
            <ol class="mb-0">
                <li>Setup database and development environment.</li>
                <li>Build core e-commerce features.</li>
                <li>Add AI recommendation and chat/voice features.</li>
                <li>Implement wholesale and discount workflow.</li>
                <li>Complete testing and quality improvements.</li>
                <li>Deploy and document the final system.</li>
            </ol>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-4 p-md-5">
            <h3 class="mb-3">Chapter 9: User Interface Design</h3>
            <ul class="mb-0">
                <li>Clean homepage with categories, occasions, and AI access.</li>
                <li>Product pages with media, options, and recommendation blocks.</li>
                <li>Simplified cart and checkout flow for quick conversion.</li>
                <li>User dashboard for orders, profile, and wishlists.</li>
                <li>Admin dashboard for product and order operations.</li>
                <li>Fully responsive design for mobile and desktop users.</li>
            </ul>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-4 p-md-5">
            <h3 class="mb-3">Chapter 10: Security Features</h3>
            <ul class="mb-0">
                <li>HTTPS-protected communication.</li>
                <li>Encrypted password storage and secure authentication.</li>
                <li>Role-based access controls (customer/admin/wholesaler).</li>
                <li>Secure payment gateway handling.</li>
                <li>Input validation and upload security.</li>
                <li>Regular backups and recovery support.</li>
            </ul>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-4 p-md-5">
            <h3 class="mb-3">Chapter 11: Testing Methodology</h3>
            <ul class="mb-0">
                <li>Functional testing for every module.</li>
                <li>User testing with real users and feedback loops.</li>
                <li>Performance testing for loading and responsiveness.</li>
                <li>Security testing for common attack prevention.</li>
                <li>Cross-browser and mobile compatibility testing.</li>
            </ul>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-4 p-md-5">
            <h3 class="mb-3">Chapter 12: Results / Expected Outcomes</h3>
            <ul class="mb-0">
                <li>Faster and more personalized outfit discovery for users.</li>
                <li>Better cultural fit and reduced return risk.</li>
                <li>Increased conversion and improved business control.</li>
                <li>Practical learning outcome for BSCS final year project goals.</li>
                <li>A deployable foundation for future commercial growth.</li>
            </ul>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-4 p-md-5">
            <h3 class="mb-3">Chapter 13: Future Enhancements</h3>
            <ul class="mb-0">
                <li>Mobile app and Urdu language support.</li>
                <li>Virtual try-on and size recommendation engine.</li>
                <li>Advanced analytics and multi-vendor capabilities.</li>
                <li>Machine learning upgrades for recommendation quality.</li>
                <li>Wider integrations and expanded business models.</li>
            </ul>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-0">
        <div class="card-body p-4 p-md-5">
            <h3 class="mb-3">Chapter 14: Conclusion</h3>
            <p>
                This AI Fashion Recommendation System presents a complete, practical, and culturally aware
                e-commerce solution for Pakistan. The project combines technical implementation with real-world
                business value and demonstrates professional-level system design, development, and deployment readiness.
            </p>
            <p class="mb-0">
                It fulfills BSCS final year project requirements while providing a strong foundation for future
                enhancement into a production-ready intelligent fashion platform.
            </p>
        </div>
    </div>
</div>

<?php include 'include/footer.php'; ?>
