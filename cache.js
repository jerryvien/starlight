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
        MsgHead.innerText = `Welcome ${UserInfo.firstname} ${UserInfo.lastname}!`;
    }
}

// Call the CheckCred function to verify credentials and update the DOM
CheckCred();

// Add the event listener for the signout button
SignoutBtn.addEventListener('click', Signout);