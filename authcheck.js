let UserCreds = JSON.parse(sessionStorage.getItem("user-creds"));
let UserInfo = JSON.parse(sessionStorage.getItem("user-info"));

let MsgHead = document.getElementById('msg');
let GreetHead = document.getElementById('greet');
let SignoutBtn = document.getElementById('signoutbutton');

let Signout = () => {
    sessionStorage.removeItem("user-creds");
    sessionStorage.removeItem("user-info");
    window.location.href = 'login.html';
}

let CheckCred = () => {
    // Check if the user is NOT logged in and redirect to login page
    if (!sessionStorage.getItem("user-creds")) {
        window.location.href = 'login.html';
    } else {
        // Verify if the user is admin
        if (UserInfo && UserInfo.isAdmin) {
            MsgHead.innerText = `User with email "${UserCreds.email}" logged in`;
            GreetHead.innerText = `Welcome ${UserInfo.firstname} ${UserInfo.lastname}!`;
        } else {
            // Redirect non-admin users
            window.location.href = 'indexemployee.html';
        }
    }
}

// Call the CheckCred function to verify credentials and update the DOM
CheckCred();

// Add the event listener for the signout button
SignoutBtn.addEventListener('click', Signout);