let slides = document.querySelectorAll(".slide");
let dotsContainer = document.getElementById("dots");
let currentIndex = 0;

// Create dots dynamically
slides.forEach((_, i) => {
  let dot = document.createElement("span");
  dot.classList.add("dot");
  if(i===0) dot.classList.add("active");
  dot.addEventListener("click", ()=> showSlide(i));
  dotsContainer.appendChild(dot);
});

let dots = document.querySelectorAll(".dot");

function showSlide(index){
  slides[currentIndex].classList.remove("active");
  dots[currentIndex].classList.remove("active");
  currentIndex = index;
  slides[currentIndex].classList.add("active");
  dots[currentIndex].classList.add("active");
}

function nextSlide(){
  let nextIndex = (currentIndex+1)%slides.length;
  showSlide(nextIndex);
}

let slideInterval = setInterval(nextSlide, 3000);
const slideshowContainer = document.querySelector('.slideshow-container');
slideshowContainer.addEventListener('mouseenter', ()=> clearInterval(slideInterval));
slideshowContainer.addEventListener('mouseleave', ()=> slideInterval = setInterval(nextSlide,3000));

function closeWelcomePanel(){
  document.getElementById("welcomePanel").style.display = "none";
}
