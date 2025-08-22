let slides = document.querySelectorAll(".slide");
let dotsContainer = document.getElementById("dots");
let currentIndex = 0;
window.addEventListener('load', () => {
  const notify = document.getElementById('notify');
  notify.classList.add('show');
  setTimeout(() => {
    notify.classList.remove('show');
  }, 4000); // disappears after 4 seconds
});

// create dots dynamically
slides.forEach((_, i) => {
  let dot = document.createElement("span");
  dot.classList.add("dot");
  if (i === 0) dot.classList.add("active");
  dot.addEventListener("click", () => showSlide(i));
  dotsContainer.appendChild(dot);
});

let dots = document.querySelectorAll(".dot");

function showSlide(index) {
  slides[currentIndex].classList.remove("active");
  dots[currentIndex].classList.remove("active");

  currentIndex = index;

  slides[currentIndex].classList.add("active");
  dots[currentIndex].classList.add("active");
}
window.addEventListener('load', () => {
  const panel = document.getElementById('welcomePanel');
  panel.classList.add('show');

  // Hide after 6 seconds
  setTimeout(() => {
    panel.classList.remove('show');
  }, 6000);
});


function nextSlide() {
  let nextIndex = (currentIndex + 1) % slides.length;
  showSlide(nextIndex);
}

setInterval(nextSlide, 3000);
