document.addEventListener("DOMContentLoaded", () => {
    const allocateEmployeeForm = document.getElementById("allocateEmployeeForm");
    const employeeEmailInput = document.getElementById("employeeEmail");
    const dispenserInput = document.getElementById("dispenserToAllocate");
  
    // Function to fetch and populate employee dropdown
    function fetchAndPopulateEmployees() {
      fetch("https://fsys-api.azurewebsites.net/api/employees")
        .then((response) => response.json())
        .then((data) => {
          if (data.isSuccess) {
            data.value.forEach((employee) => {
              const option = document.createElement("option");
              option.value = employee.email;
              option.textContent = `${employee.firstName} ${employee.lastName}`;
              employeeEmailInput.appendChild(option);
            });
          } else {
            console.error("Failed to fetch employees:", data.error.message);
          }
        })
        .catch((error) => {
          console.error("Error fetching employees:", error);
        });
    }
  
    // Function to handle form submission
    function handleFormSubmission(event) {
      event.preventDefault();
  
      const selectedEmployeeEmail = employeeEmailInput.value;
      const selectedDispenser = dispenserInput.value;
  
      // Send email to the selected employee
      sendEmail(selectedEmployeeEmail, selectedDispenser);
    }
  
    // Function to send email using EmailJS
    function sendEmail(employeeEmail, selectedDispenser) {
      // Replace with your EmailJS public key and service/template IDs
      emailjs.init('0kDn7yfvpYkk-DGka');
  
      const templateParams = {
        to_email: employeeEmail,
        dispenser_number: selectedDispenser,
      };
  
      emailjs.send("service_klxig5a", "template_6g80apk", templateParams)
        .then(function (response) {
          console.log("Email sent successfully!", response);
          alert("Email sent successfully!");
        })
        .catch(function (error) {
          console.error("Failed to send email:", error);
          alert("Failed to send email. Please try again later.");
        });
    }
  
    // Populate employees dropdown on page load
    fetchAndPopulateEmployees();
  
    // Add form submission event listener
    allocateEmployeeForm.addEventListener("submit", handleFormSubmission);
  });
  