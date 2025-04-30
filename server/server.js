const express = require('express');
const bodyParser = require('body-parser');
const fs = require('fs');
const path = require('path');
const nodemailer = require('nodemailer');
const cors = require('cors');

const app = express();
const PORT = 3001; // Changed from 5500 to avoid conflict with Live Server

// Enhanced CORS configuration
app.use(cors({
    origin: '*', // Allow all origins for development
    methods: ['GET', 'POST'],
    allowedHeaders: ['Content-Type']
}));

// Middleware
app.use(bodyParser.json());
app.use(bodyParser.urlencoded({ extended: true }));
app.use(express.static(path.join(__dirname, 'public')));

// Email configuration
const transporter = nodemailer.createTransport({
    service: 'gmail',
    auth: {
        user: 'kabirydv9090@gmail.com',
        pass: 'xioi ivoh gyqi kqkq'
    }
});

const registrationsFile = 'registrations.json';

// Initialize file if it doesn't exist
if (!fs.existsSync(registrationsFile)) {
    fs.writeFileSync(registrationsFile, '[]');
}

// Registration endpoint with improved error handling
app.post('/register', async (req, res) => {
  try {
      const { name, email, phone, offer, course } = req.body;
      
      // Validate required fields
      if (!name || !email || !phone || !offer || !course) {
          return res.status(400).json({
              success: false,
              message: 'All fields are required'
          });
      }

      // Read existing registrations
      let registrations = [];
      try {
          registrations = JSON.parse(fs.readFileSync(registrationsFile));
      } catch (err) {
          console.error('Error reading registrations file:', err);
      }

      // Add new registration
      const newRegistration = {
          name,
          email,
          phone,
          offer,
          course,
          registrationDate: new Date().toISOString()
      };
      
      registrations.push(newRegistration);

      // Save to file
      fs.writeFileSync(registrationsFile, JSON.stringify(registrations, null, 2));

      // Send email - Update the email subject to include the offer
      const mailOptions = {
          from: 'kabirydv9090@gmail.com',
          to: email,
          subject: `🎯 ${offer} - Enrollment Confirmed!`,
          html: `
  <div style="font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; max-width: 600px; margin: 0 auto; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.1);">
    <!-- Header -->
    <div style="background: linear-gradient(135deg, #2563eb 0%, #4f46e5 100%); padding: 30px 20px; text-align: center;">
      <h1 style="color: white; margin: 0; font-size: 28px; font-weight: 600;">Journey Begins - ${offer}</h1>
      <p style="color: rgba(255,255,255,0.8); margin: 8px 0 0; font-size: 16px;">Your Journey Begins Here</p>
    </div>

    <!-- Main Content -->
    <div style="padding: 30px; background: #ffffff;">
      <div style="text-align: center; margin-bottom: 25px;">
        <svg width="60" height="60" viewBox="0 0 24 24" fill="none">
          <path d="M9 12L11 14L15 10M21 12C21 16.9706 16.9706 21 12 21C7.02944 21 3 16.9706 3 12C3 7.02944 7.02944 3 12 3C16.9706 3 21 7.02944 21 12Z" stroke="#4f46e5" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
        <h2 style="color: #111827; margin: 15px 0 10px; font-size: 22px; font-weight: 600;">Welcome aboard, ${name}!</h2>
        <p style="color: #4b5563; margin: 0; font-size: 15px;">Your registration for our ${offer} program is confirmed.</p>
      </div>

      <!-- Details Card -->
      <div style="background: #f9fafb; border-radius: 10px; padding: 20px; margin-bottom: 25px; border: 1px solid #e5e7eb;">
        <h3 style="color: #111827; margin-top: 0; margin-bottom: 15px; font-size: 18px; font-weight: 600;">Enrollment Details</h3>
        <table style="width: 100%; border-collapse: collapse;">
          <tr>
            <td style="padding: 8px 0; width: 100px; color: #6b7280; font-size: 14px;">Student Name</td>
            <td style="padding: 8px 0; font-weight: 500; font-size: 15px;">${name}</td>
          </tr>
          <tr>
            <td style="padding: 8px 0; color: #6b7280; font-size: 14px;">Course Offer</td>
            <td style="padding: 8px 0; font-weight: 500; font-size: 15px;">${offer}</td>
          </tr>
          <tr>
            <td style="padding: 8px 0; color: #6b7280; font-size: 14px;">Pricing Plan</td>
            <td style="padding: 8px 0; font-weight: 500; font-size: 15px;">${course}</td>
          </tr>
          <tr>
            <td style="padding: 8px 0; color: #6b7280; font-size: 14px;">Enrollment ID</td>
            <td style="padding: 8px 0; font-weight: 500; font-size: 15px;">DS-${Math.floor(100000 + Math.random() * 900000)}</td>
          </tr>
          <tr>
            <td style="padding: 8px 0; color: #6b7280; font-size: 14px;">Date</td>
            <td style="padding: 8px 0; font-weight: 500; font-size: 15px;">${new Date().toLocaleDateString('en-US', { weekday: 'short', year: 'numeric', month: 'short', day: 'numeric' })}</td>
          </tr>
        </table>
      </div>

      <!-- What's Next Section -->
      <div style="margin-bottom: 25px;">
        <h3 style="color: #111827; margin-bottom: 12px; font-size: 18px;">What's Next?</h3>
        <ul style="list-style: none; padding: 0; color: #4b5563; font-size: 15px; line-height: 1.8;">
          <li><strong>1.</strong> You'll receive course access credentials within 24 hours</li>
          <li><strong>2.</strong> Check your inbox for orientation materials and schedule</li>
          <li><strong>3.</strong> Join our exclusive student community platform</li>
        </ul>
        <div style="text-align: center; margin-top: 20px;">
          <a href="#" style="display: inline-block; padding: 12px 24px; background: linear-gradient(135deg, #3b82f6, #6366f1); color: white; text-decoration: none; font-weight: 600; border-radius: 8px;">Get Started with Course</a>
        </div>
      </div>
    </div>

    <!-- Footer -->
    <div style="background: #f3f4f6; padding: 16px 30px; text-align: center; font-size: 13px; color: #6b7280;">
      <p style="margin: 0;">Need assistance? We're here to help!</p>
      <p style="margin: 0;"><a href="#" style="color: #6366f1; text-decoration: underline;">Contact Support Team</a></p>
    </div>
  </div>
`

      };
      
      await transporter.sendMail(mailOptions);
      
      res.json({
          success: true,
          message: 'Registration successful! Check your email for confirmation.',
          data: newRegistration
      });

  } catch (error) {
      console.error('Registration error:', error);
      res.status(500).json({
          success: false,
          message: 'Registration failed. Please try again later.',
          error: error.message
      });
  }
});
// Start server
app.listen(PORT, () => {
    console.log(`Server running on http://localhost:${PORT}`);
});