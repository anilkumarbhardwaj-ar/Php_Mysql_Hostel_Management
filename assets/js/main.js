// Main JavaScript file for the Hostel Management System

// Toggle mobile navigation
document.addEventListener("DOMContentLoaded", () => {
  // Add mobile navigation toggle if it exists
  const navToggle = document.querySelector(".nav-toggle")
  const navMenu = document.querySelector("nav ul")

  if (navToggle && navMenu) {
    navToggle.addEventListener("click", () => {
      navMenu.classList.toggle("show")
    })
  }

  // Initialize any date pickers
  const datePickers = document.querySelectorAll('input[type="date"]')

  datePickers.forEach((picker) => {
    // Set default value to today if not already set
    if (!picker.value) {
      picker.valueAsDate = new Date()
    }
  })

  // Add event listeners for delete confirmations
  const deleteButtons = document.querySelectorAll(".btn-delete")

  deleteButtons.forEach((button) => {
    button.addEventListener("click", (e) => {
      if (!confirm("Are you sure you want to delete this item? This action cannot be undone.")) {
        e.preventDefault()
      }
    })
  })

  // Add AJAX form submission for search forms
  const searchForms = document.querySelectorAll(".search-form")

  searchForms.forEach((form) => {
    form.addEventListener("submit", function (e) {
      e.preventDefault()

      const formData = new FormData(this)
      const url = this.getAttribute("action")
      const resultContainer = document.querySelector(this.dataset.results)

      if (resultContainer) {
        // Show loading indicator
        resultContainer.innerHTML = '<div class="loading-indicator">Loading...</div>'

        fetch(url, {
          method: "POST",
          body: formData,
        })
          .then((response) => response.text())
          .then((data) => {
            resultContainer.innerHTML = data
          })
          .catch((error) => {
            console.error("Error:", error)
            resultContainer.innerHTML = '<div class="error">An error occurred. Please try again.</div>'
          })
      }
    })
  })

  // Add real-time validation for forms
  const forms = document.querySelectorAll("form")

  forms.forEach((form) => {
    const inputs = form.querySelectorAll("input, select, textarea")

    inputs.forEach((input) => {
      input.addEventListener("blur", () => {
        validateInput(input)
      })
    })

    form.addEventListener("submit", (e) => {
      let isValid = true

      inputs.forEach((input) => {
        if (!validateInput(input)) {
          isValid = false
        }
      })

      if (!isValid) {
        e.preventDefault()
      }
    })
  })

  // Input validation function
  function validateInput(input) {
    const value = input.value.trim()
    const errorElement = input.nextElementSibling

    // Remove any existing error message
    if (errorElement && errorElement.classList.contains("error-message")) {
      errorElement.remove()
    }

    // Check if required field is empty
    if (input.hasAttribute("required") && value === "") {
      const errorMessage = document.createElement("div")
      errorMessage.className = "error-message"
      errorMessage.textContent = "This field is required"
      input.parentNode.insertBefore(errorMessage, input.nextSibling)
      input.classList.add("error")
      return false
    }

    // Check email format
    if (input.type === "email" && value !== "") {
      const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
      if (!emailRegex.test(value)) {
        const errorMessage = document.createElement("div")
        errorMessage.className = "error-message"
        errorMessage.textContent = "Please enter a valid email address"
        input.parentNode.insertBefore(errorMessage, input.nextSibling)
        input.classList.add("error")
        return false
      }
    }

    // Check minimum length
    if (input.hasAttribute("minlength") && value !== "") {
      const minLength = Number.parseInt(input.getAttribute("minlength"))
      if (value.length < minLength) {
        const errorMessage = document.createElement("div")
        errorMessage.className = "error-message"
        errorMessage.textContent = `This field must be at least ${minLength} characters`
        input.parentNode.insertBefore(errorMessage, input.nextSibling)
        input.classList.add("error")
        return false
      }
    }

    // Input is valid
    input.classList.remove("error")
    return true
  }
})

// Function to format currency
function formatCurrency(amount) {
  return new Intl.NumberFormat("en-IN", {
    style: "currency",
    currency: "INR",
    minimumFractionDigits: 0,
  }).format(amount)
}

// Function to format date
function formatDate(dateString) {
  const options = { year: "numeric", month: "short", day: "numeric" }
  return new Date(dateString).toLocaleDateString("en-US", options)
}

// Function to show notification
function showNotification(message, type = "success") {
  const notification = document.createElement("div")
  notification.className = `notification ${type}`
  notification.textContent = message

  document.body.appendChild(notification)

  // Show notification
  setTimeout(() => {
    notification.classList.add("show")
  }, 10)

  // Hide and remove notification after 3 seconds
  setTimeout(() => {
    notification.classList.remove("show")

    // Remove from DOM after animation completes
    setTimeout(() => {
      notification.remove()
    }, 300)
  }, 3000)
}

// Function to confirm action
function confirmAction(message) {
  return confirm(message || "Are you sure you want to proceed?")
}

// Function to handle AJAX errors
function handleAjaxError(error) {
  console.error("AJAX Error:", error)
  showNotification("An error occurred. Please try again.", "error")
}
