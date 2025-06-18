
// Dropdown profil
const profile = document.querySelector('.profile');
const dropdown = document.querySelector('.dropdown-menu');

profile.addEventListener('click', () => {
  dropdown.style.display = dropdown.style.display === 'block' ? 'none' : 'block';
});

document.addEventListener('click', (e) => {
  if (!document.querySelector('.profile-menu').contains(e.target)) {
    dropdown.style.display = 'none';
  }
});

// Switch tombol add wishlist
document.getElementById("show-form").addEventListener("click", function () {
  const form = document.getElementById("add-form");
  const text = document.getElementById("add-text");

  if (form.style.display === "none" || form.style.display === "") {
    form.style.display = "inline-block";
    text.style.display = "none";
  } 
  
  else {
    form.style.display = "none";
    text.style.display = "inline-block";
  }
});