
// window.onload = function () {

//      var counters = document.querySelectorAll('.counter[data-target]');
//     if (counters.length) {
//         var animateCounter = function (el) {
//             var target = parseInt(el.getAttribute('data-target'), 10) || 0;
//             var duration = 1400;
//             var start = null;

//             function step(timestamp) {
//                 if (!start) start = timestamp;
//                 var progress = Math.min((timestamp - start) / duration, 1);
//                 var eased = 1 - Math.pow(1 - progress, 3);
//                 el.textContent = Math.floor(eased * target).toLocaleString();
//                 if (progress < 1) {
//                     window.requestAnimationFrame(step);
//                 } else {
//                     el.textContent = target.toLocaleString();
//                 }
//             }
//             window.requestAnimationFrame(step);
//         };

//         if ('IntersectionObserver' in window) {
//             var observer = new IntersectionObserver(function (entries) {
//                 entries.forEach(function (entry) {
//                     if (entry.isIntersecting) {
//                         animateCounter(entry.target);
//                         observer.unobserve(entry.target);
//                     }
//                 });
//             }, { threshold: 0.4 });

//             counters.forEach(function (el) { observer.observe(el); });
//         } else {
//             counters.forEach(animateCounter);
//         }
//     }
// };


//     var roleRadios = document.getElementsByName("role");
// // Check a condition before running the following statements.
//     if (roleRadios.length > 0) {
//         for (var r = 0; r < roleRadios.length; r++) {
//             roleRadios[r].onchange = function () {
//                 var donorFields = document.getElementById("donorFields");
//                 var hospitalFields = document.getElementById("hospitalFields");
// // Check a condition before running the following statements.
//                 if (donorFields) donorFields.style.display = "none";
// // Check a condition before running the following statements.
//                 if (hospitalFields) hospitalFields.style.display = "none";
// // Check a condition before running the following statements.
//                 if (this.value == "donor" && donorFields) donorFields.style.display = "block";
// // Check a condition before running the following statements.
//                 if (this.value == "hospital" && hospitalFields) hospitalFields.style.display = "block";
//             };
//         }
//     }

//     var autoSubmitSelects = document.querySelectorAll("select[data-auto-submit]");
//     for (var i = 0; i < autoSubmitSelects.length; i++) {
//         autoSubmitSelects[i].onchange = function () { this.form.submit(); };
    

//     var successBoxes = document.getElementsByClassName("alert-success");
//     for (var s = 0; s < successBoxes.length; s++) {
//         (function (box) { setTimeout(function () { box.style.display = "none"; }, 5000); })(successBoxes[s]);
//     }

//     var confirmButtons = document.querySelectorAll("[data-confirm]");
//     for (var c = 0; c < confirmButtons.length; c++) {
//         confirmButtons[c].onclick = function () { return confirm(this.getAttribute("data-confirm")); };
//     }

    
// };



document.addEventListener('DOMContentLoaded', function () {

    /* ---------- Mobile hamburger nav ---------- */
    var toggle   = document.getElementById('navToggle');
    var firstUl  = document.getElementById('firstUl');
    var secondUl = document.getElementById('secondUl');
    var backdrop = document.getElementById('navBackdrop');

    function closeNav() {
        toggle.classList.remove('active');
        firstUl.classList.remove('active');
        secondUl.classList.remove('active');
        backdrop.classList.remove('active');
        toggle.setAttribute('aria-expanded', 'false');
        document.body.classList.remove('nav-open');
    }

    function openNav() {
        toggle.classList.add('active');
        firstUl.classList.add('active');
        secondUl.classList.add('active');
        backdrop.classList.add('active');
        toggle.setAttribute('aria-expanded', 'true');
        document.body.classList.add('nav-open');
    }

    if (toggle && firstUl && secondUl && backdrop) {
        toggle.addEventListener('click', function () {
            var isOpen = toggle.classList.contains('active');
            isOpen ? closeNav() : openNav();
        });

        backdrop.addEventListener('click', closeNav);

        // Close the menu whenever a nav link is tapped
        document.querySelectorAll('.first-ul a, .second-ul a').forEach(function (link) {
            link.addEventListener('click', closeNav);
        });

        // Close on resize back up to desktop
        window.addEventListener('resize', function () {
            if (window.innerWidth > 768) closeNav();
        });
    }
});