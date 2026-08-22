// This replaces the PHP foreach loop
const articles = [
    {
        title: "SSLG General Assembly",
        category: "Announcements",
        description: "Join us for the upcoming general assembly to discuss our new campus projects.",
        image: "images/slide1.jpg",
        link: "#"
    },
    {
        title: "Celebrating Independence Day",
        category: "Announcements",
        description: "Let us remember the heroes who fought for our liberty.",
        image: "images/independence.jpg",
        link: "#"
    }
];

const list = document.getElementById('article-list');

articles.forEach((article, index) => {
    const item = document.createElement('div');
    item.className = `item ${index === 0 ? 'active' : ''}`;
    item.innerHTML = `
        <img src="${article.image}">
        <div class="content">
            <div class="title">${article.title}</div>
            <div class="name">${article.category}</div>
            <div class="des">${article.description}</div>
            <div class="btn">
                <a href="${article.link}">See More</a>
            </div>
        </div>
    `;
    list.appendChild(item);
});

// Function to handle moving to next/prev slides
function showSlider(type) {
    let items = document.querySelectorAll('.carousel .list .item');
    if (type === 'next') {
        let activeItem = document.querySelector('.carousel .list .item.active');
        activeItem.classList.remove('active');
        if (activeItem.nextElementSibling) {
            activeItem.nextElementSibling.classList.add('active');
        } else {
            items[0].classList.add('active');
        }
    } else {
        // Handle 'prev' similarly...
    }
}

// Add event listeners to buttons
document.querySelector('.next').addEventListener('click', () => showSlider('next'));
document.querySelector('.prev').addEventListener('click', () => showSlider('prev'));