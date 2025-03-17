<div class="user-dashboard">
    <div class="user-navbar">
        <ul>
            <li><a href="dashboard.php"><button class="btn btn-danger"><?php echo LANG_VALUE_89; ?></button></a></li>
            <li><a href="customer-profile-update.php"><button class="btn btn-danger"><?php echo LANG_VALUE_117; ?></button></a></li>
            <li><a href="customer-billing-shipping-update.php"><button class="btn btn-danger"><?php echo LANG_VALUE_88; ?></button></a></li>
            <li><a href="customer-password-update.php"><button class="btn btn-danger"><?php echo LANG_VALUE_99; ?></button></a></li>
            <li><a href="customer-order.php"><button class="btn btn-danger"><?php echo LANG_VALUE_24; ?></button></a></li>
            <li><a href="logout.php"><button class="btn btn-danger"><?php echo LANG_VALUE_14; ?></button></a></li>
        </ul>
    </div>
</div>

<style>
/* User Dashboard Container */
.user-dashboard {
  width: 100%; /* Full width */
  background-color: #f8f9fa; /* Light background color */
  padding: 10px 20px; /* Padding for spacing */
  box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1); /* Subtle shadow for depth */
}

/* User Navbar */
.user-navbar ul {
  list-style: none; /* Remove bullet points */
  padding: 0;
  margin: 0;
  display: flex; /* Flexbox for horizontal layout */
  justify-content: flex-start; /* Align items to the left */
  gap: 10px; /* Spacing between buttons */
}

/* Navbar Links */
.user-navbar ul a {
  text-decoration: none; /* Remove underline */
}

/* Navbar Buttons */
.user-navbar ul a button {
  padding: 12px 20px; /* Padding for better clickability */
  background-color: #ff6600; /* Orange background */
  color: #fff; /* White text */
  border: none; /* Remove default border */
  border-radius: 20px; /* Rounded corners */
  font-size: 16px; /* Font size */
  font-weight: 500; /* Medium font weight */
  cursor: pointer; /* Pointer cursor on hover */
  transition: all 0.3s ease; /* Smooth transition for hover effects */
}

/* Hover Effect for Buttons */
.user-navbar ul a button:hover {
  background-color: #e65c00; /* Darker orange on hover */
  transform: translateY(-2px); /* Slight lift effect */
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2); /* Shadow on hover */
}

/* Active Button Style */
.user-navbar ul a button:active {
  transform: translateY(0); /* Reset lift effect */
  box-shadow: none; /* Remove shadow */
}

/* Responsive Design for Mobile */
@media only screen and (max-width: 768px) {
  .user-navbar ul {
    flex-direction: column; /* Stack buttons vertically on mobile */
    gap: 5px; /* Reduce spacing between buttons */
  }

  .user-navbar ul a button {
    width: 100%; /* Full width on mobile */
    padding: 10px 15px; /* Smaller padding for mobile */
    font-size: 14px; /* Smaller font size */
  }
}
</style>