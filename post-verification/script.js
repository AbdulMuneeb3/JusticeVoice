// Initialize arrays to keep track of posts
let pendingPosts = Array.from(document.querySelectorAll('#pending-posts .post')).map(post => ({
    id: post.dataset.postId,
    element: post
}));
let approvedPosts = [];
let rejectedPosts = [];

// Create a post element based on the post data
function createPostElement(post) {
    const postDiv = document.createElement('div');
    postDiv.className = 'post';
    postDiv.dataset.postId = post.id;
    postDiv.innerHTML = `
        <div class="post-content">${post.post_on}</div>
        <div class="post-description">${post.post_description}</div> <!-- Display post_description -->
        <div class="post-actions">
            <button class="approve-btn">Approve</button>
            <button class="reject-btn">Reject</button>
        </div>
    `;
    return postDiv;
}

// Move post between arrays and containers
function movePost(postId, fromArray, toArray, toContainer) {
    const index = fromArray.findIndex(p => p.id === postId);
    if (index !== -1) {
        const [post] = fromArray.splice(index, 1);
        toArray.push(post);
        toContainer.appendChild(post.element);

        // Remove action buttons if moving to approved or rejected sections
        if (toContainer.id === 'approved-posts' || toContainer.id === 'rejected-posts') {
            const actions = post.element.querySelector('.post-actions');
            if (actions) actions.remove();
        }

        // Reinitialize event listeners for new elements if applicable
        initPostButtons();
    } else {
        console.error(`Post with ID ${postId} not found in the array.`);
    }
}

// Initialize event listeners for approve/reject buttons
function initPostButtons() {
    // Initialize event listeners for approve buttons
    const approveButtons = document.querySelectorAll('.approve-btn');
    approveButtons.forEach(btn => {
        btn.removeEventListener('click', approvePost); // Remove any existing listener
        btn.addEventListener('click', approvePost);
    });

    // Initialize event listeners for reject buttons
    const rejectButtons = document.querySelectorAll('.reject-btn');
    rejectButtons.forEach(btn => {
        btn.removeEventListener('click', rejectPost); // Remove any existing listener
        btn.addEventListener('click', rejectPost);
    });
}

// Send AJAX request to update post status in the database
function updatePostStatus(postId, status) {
    console.log(`Updating post ${postId} to status: ${status}`);
    return fetch('update_post_status.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ post_id: postId, status: status })
    }).then(response => {
        if (!response.ok) throw new Error('Network response was not ok');
        return response.json();
    }).then(data => {
        if (!data.success) {
            throw new Error(data.error || 'Unknown error');
        }
        return data;
    }).catch(error => {
        console.error('Error with fetch request:', error);
        return { success: false, message: error.message };
    });
}

// Approve a post and move it to the approved section
function approvePost(event) {
    const postId = event.target.closest('.post').dataset.postId;
    updatePostStatus(postId, 'approved').then(response => {
        if (response && response.success) {
            console.log(`Post ${postId} approved successfully.`);
            movePost(postId, pendingPosts, approvedPosts, document.getElementById('approved-posts'));
        } else {
            console.error('Failed to approve post:', response ? response.message : 'Unknown error');
        }
    });
}

// Reject a post and move it to the rejected section
function rejectPost(event) {
    const postId = event.target.closest('.post').dataset.postId;
    updatePostStatus(postId, 'rejected').then(response => {
        if (response && response.success) {
            console.log(`Post ${postId} rejected successfully.`);
            movePost(postId, pendingPosts, rejectedPosts, document.getElementById('rejected-posts'));
        } else {
            console.error('Failed to reject post:', response ? response.message : 'Unknown error');
        }
    });
}

// Switch between sections (Pending, Approved, Rejected)
function switchSection(sectionId) {
    document.querySelectorAll('.section').forEach(section => {
        section.classList.remove('active');
    });
    document.getElementById(`${sectionId}-section`).classList.add('active');

    // Update navigation links to highlight the current section
    document.querySelectorAll('.nav-links a').forEach(link => {
        link.classList.remove('active');
    });
    document.querySelector(`.nav-links a[data-section="${sectionId}"]`).classList.add('active');
}

// Initialize event listeners for navigation links
function init() {
    initPostButtons(); // Initialize buttons once on page load

    // Initialize navigation link event listeners
    document.querySelectorAll('.nav-links a').forEach(link => {
        link.addEventListener('click', (event) => {
            event.preventDefault();
            switchSection(event.target.dataset.section);
        });
    });

    console.log('Event listeners initialized.');
}

window.onload = init;
