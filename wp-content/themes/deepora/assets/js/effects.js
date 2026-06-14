//Blur Animations
const observerBlur = new IntersectionObserver((entries) => {
    entries.forEach((entry) => {
      if (entry.isIntersecting) {
        entry.target.classList.add('appear-effect-show');
      } else {
        // entry.target.classList.remove('item-blur-effect');
      }
    });
  });
  
  const blurElements = document.querySelectorAll('.appear-effect');
  blurElements.forEach((el) => observerBlur.observe(el));
  
  //Slide Left Animation
  const observerSlideLeft = new IntersectionObserver((entries) => {
    entries.forEach((entry) => {
      if (entry.isIntersecting) {
        entry.target.classList.add('item-slide-left-effect');
      } else {
        // entry.target.classList.remove('item-slide-left-effect');
      }
    });
  });
  
  const slideLeftElements = document.querySelectorAll('.item-slide-left');
  slideLeftElements.forEach((el) => observerSlideLeft.observe(el));
  
  //Slide Up Animation
  const observerSlideUp = new IntersectionObserver((entries) => {
    entries.forEach((entry) => {
      if (entry.isIntersecting) {
        entry.target.classList.add('slide-up-expand-effect');
      } else {
        // entry.target.classList.remove('item-slide-up-effect');
      }
    });
  });
  
  const slideUpElements = document.querySelectorAll('.slide-up-expand');
  slideUpElements.forEach((el) => observerSlideUp.observe(el));
  
  //Slide Down Animation
  const observerSlideDown = new IntersectionObserver((entries) => {
    entries.forEach((entry) => {
      if (entry.isIntersecting) {
        entry.target.classList.add('item-slide-down-effect');
      } else {
        // entry.target.classList.remove('item-slide-up-effect');
      }
    });
  });
  
  const slideDownElements = document.querySelectorAll('.item-slide-down');
  slideDownElements.forEach((el) => observerSlideDown.observe(el));
  
  //Scale Animation
  const observerScale = new IntersectionObserver((entries) => {
    entries.forEach((entry) => {
      if (entry.isIntersecting) {
        entry.target.classList.add('item-scale-effect');
      } else {
        // entry.target.classList.remove('item-scale-effect');
      }
    });
  });
  
  const scaleElements = document.querySelectorAll('.item-scale');
  scaleElements.forEach((el) => observerScale.observe(el));
  
  //Left Blur Slider Animation
  const observerLeftBlurSlider = new IntersectionObserver((entries) => {
    entries.forEach((entry) => {
      if (entry.isIntersecting) {
        entry.target.classList.add('left-blur-slide-effect');
      } else {
        // entry.target.classList.remove('left-blur-slide-effect');
      }
    });
  });
  
  const leftBlurSliderElements = document.querySelectorAll('.left-blur-slide');
  leftBlurSliderElements.forEach((el) => observerLeftBlurSlider.observe(el));
  