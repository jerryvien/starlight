

// Function to register user
function registerUser(userData) {
    set(ref(database, `users/${userData.userId}`), {
        username: userData.username,
        email: userData.email,
        // Other user data
    })
    .then(() => {
        console.log('User registration successful');
        // Handle success
    })
    .catch((error) => {
        console.error('Error registering user:', error);
        // Handle error
    });
}
