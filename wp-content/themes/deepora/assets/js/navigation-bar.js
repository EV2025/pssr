document.addEventListener("scroll", function () {
  const topNavigationBar = document.querySelector(".top-navigation-bar");
  const menuWrapper = document.querySelector(".menu-content-wrapper");
  const scrollPosition = window.scrollY;

  if (scrollPosition > 0) {
    topNavigationBar.classList.add("fixed");
  } else {
    topNavigationBar.classList.remove("fixed");
  }

  if (menuWrapper) {
    if (scrollPosition > 100) {
      menuWrapper.classList.add("scrolled");
    } else {
      menuWrapper.classList.remove("scrolled");
    }
  }
});
