# Complete Testing Scenario: Phone & Video Calls

## 🎯 Scenario Overview
Patient books a video/phone appointment → Doctor confirms → They connect via call/video

---

## 📋 SCENARIO 1: VIDEO CALL APPOINTMENT

### Step 1: Doctor Setup (5 minutes)
1. **Login as Doctor**
   - Go to: http://127.0.0.1:8000/login
   - Login with doctor credentials

2. **Enable Video Calls**
   - Go to: http://127.0.0.1:8000/doctor/settings/appointments
   - Toggle ON "Video Call"
   - Click "Save Changes"
   - ✅ You should see: "Appointment type preferences updated successfully!"

### Step 2: Patient Books Video Appointment (3 minutes)
1. **Login as Patient** (or use guest booking)
   - Go to: http://127.0.0.1:8000/login
   - Login with patient credentials

2. **Book Video Appointment**
   - Go to: http://127.0.0.1:8000/doctors
   - Click on a doctor
   - Click "Book Appointment"
   - Select appointment type: **Video Call**
   - Choose date/time
   - Fill reason: "Need consultation about headache"
   - Click "Book Appointment"
   - ✅ You should see: "Appointment booked successfully!"

### Step 3: Doctor Confirms Appointment (2 minutes)
1. **Go to Doctor Dashboard**
   - Go to: http://127.0.0.1:8000/doctor/appointments
   - Find the pending appointment
   - Click "View Details"

2. **Confirm the Appointment**
   - Click "Confirm Appointment" button
   - ✅ Status changes to "Confirmed"
   - ✅ You should now see: **"Start Video Call"** button (blue button with video icon)

### Step 4: Start Video Call (Doctor Side)
1. **Click "Start Video Call" Button**
   - A new tab/window opens
   - ✅ You should see:
     - "Connecting..." message
     - Your camera feed in bottom-right corner
     - Large area for patient video
     - Control buttons at bottom (Mute, Camera, End Call)

2. **Grant Camera/Microphone Permissions**
   - Browser will ask for permissions
   - Click "Allow"
   - ✅ Your video should appear in small window (bottom-right)

3. **Wait for Patient to Join**
   - Status shows: "Connected"
   - Waiting for patient...

### Step 5: Patient Joins Video Call (Patient Side)
1. **Patient Gets Notification**
   - Patient receives email/notification about confirmed appointment
   - Or patient checks appointment details

2. **Patient Opens Appointment**
   - Go to: http://127.0.0.1:8000/appointments
   - Click on the confirmed appointment
   - ✅ Patient should see: **"Start Video Call"** button

3. **Patient Clicks "Start Video Call"**
   - New tab opens
   - Grant camera/microphone permissions
   - ✅ Patient video appears in small window
   - ✅ Doctor video appears in large window
   - ✅ Both can see each other!

### Step 6: During Video Call
**Both Doctor and Patient can:**
- 🎤 **Mute/Unmute**: Click microphone button
- 📹 **Camera On/Off**: Click camera button
- 📞 **End Call**: Click red phone button

**Test the controls:**
1. Doctor clicks mute → Patient can't hear doctor
2. Patient clicks camera off → Doctor sees black screen
3. Both can see participant names displayed

### Step 7: End Video Call
1. **Doctor Clicks "End Call"**
   - Call disconnects
   - Redirects to appointments page
   - ✅ Appointment status changes to "Completed"

---

## 📋 SCENARIO 2: PHONE CALL APPOINTMENT

### Step 1: Doctor Setup (5 minutes)
1. **Login as Doctor**
   - Go to: http://127.0.0.1:8000/login

2. **Enable Phone Calls**
   - Go to: http://127.0.0.1:8000/doctor/settings/appointments
   - Toggle ON "Phone Call"
   - Click "Save Changes"

### Step 2: Verify Patient Phone Number in Twilio (IMPORTANT!)
⚠️ **For trial accounts, you MUST verify the patient's phone number first**

1. **Go to Twilio Console**
   - Visit: https://console.twilio.com/us1/develop/phone-numbers/manage/verified
   - Click "Add a new number"
   - Enter patient's phone number (e.g., +1234567890)
   - Twilio will send verification code to that number
   - Enter the code to verify
   - ✅ Number is now verified and can receive calls

### Step 3: Patient Books Phone Appointment (3 minutes)
1. **Login as Patient**
   - Go to: http://127.0.0.1:8000/login

2. **Book Phone Appointment**
   - Go to: http://127.0.0.1:8000/doctors
   - Select doctor
   - Click "Book Appointment"
   - Select appointment type: **Phone Call**
   - Choose date/time
   - **IMPORTANT**: Enter phone number (must be verified in Twilio)
   - Fill reason: "Need phone consultation"
   - Click "Book Appointment"

### Step 4: Doctor Confirms Appointment (2 minutes)
1. **Go to Appointments**
   - Go to: http://127.0.0.1:8000/doctor/appointments
   - Find pending appointment
   - Click "View Details"

2. **Confirm Appointment**
   - Click "Confirm Appointment"
   - ✅ Status: "Confirmed"
   - ✅ You should see: **"Call Patient"** button (green button with phone icon)

### Step 5: Doctor Initiates Phone Call
1. **Click "Call Patient" Button**
   - Button shows: "Calling..."
   - ✅ You should see success message: "Call initiated successfully"

2. **What Happens:**
   - Patient's phone rings immediately
   - Patient hears: "You have a trial account. Press any key to execute your code."
   - Patient presses any key
   - Call connects to doctor's phone number
   - Doctor's phone rings
   - ✅ Both are connected via phone call!

### Step 6: During Phone Call
- Normal phone conversation
- Call is tracked in Twilio console
- Call duration is recorded

### Step 7: End Phone Call
- Either party hangs up
- Call status updates automatically
- ✅ Appointment status changes to "Completed"

---

## 🧪 QUICK TEST CHECKLIST

### Video Call Test ✅
- [ ] Doctor enables video call type
- [ ] Patient books video appointment
- [ ] Doctor confirms appointment
- [ ] "Start Video Call" button appears
- [ ] Doctor clicks button → video room opens
- [ ] Patient clicks button → joins video room
- [ ] Both see each other's video
- [ ] Mute/unmute works
- [ ] Camera on/off works
- [ ] End call works
- [ ] Appointment marked as completed

### Phone Call Test ✅
- [ ] Doctor enables phone call type
- [ ] Patient phone number verified in Twilio
- [ ] Patient books phone appointment with verified number
- [ ] Doctor confirms appointment
- [ ] "Call Patient" button appears
- [ ] Doctor clicks button → patient phone rings
- [ ] Patient answers → connected to doctor
- [ ] Call completes successfully
- [ ] Appointment marked as completed

---

## 🐛 Troubleshooting

### Video Call Issues

**Problem: "Failed to connect"**
- Check browser permissions (camera/microphone)
- Try different browser (Chrome/Firefox recommended)
- Check HTTPS is enabled (required for WebRTC)

**Problem: "No video showing"**
- Grant camera permissions
- Check camera is not used by another app
- Refresh page and try again

**Problem: "Can't hear audio"**
- Check microphone permissions
- Unmute if muted
- Check system audio settings

### Phone Call Issues

**Problem: "Patient phone number not available"**
- Patient must enter phone number when booking
- Check phone number format: +1234567890

**Problem: "Call failed" (Trial Account)**
- Phone number MUST be verified in Twilio console first
- Go to: https://console.twilio.com/us1/develop/phone-numbers/manage/verified
- Add and verify the patient's number

**Problem: "Trial account message"**
- Normal for trial accounts
- Patient presses any key to continue
- Upgrade Twilio account to remove message

---

## 📊 Expected Results

### Video Call Success Indicators:
✅ Video room opens in new tab
✅ Both participants see each other
✅ Controls work (mute, camera, end)
✅ Call ends cleanly
✅ Appointment status updates

### Phone Call Success Indicators:
✅ Patient phone rings
✅ Call connects
✅ Normal phone conversation
✅ Call tracked in system
✅ Appointment status updates

---

## 💡 Pro Tips

1. **Test with two browsers**: Open doctor in Chrome, patient in Firefox
2. **Use incognito mode**: Prevents session conflicts
3. **Check Twilio logs**: https://console.twilio.com/us1/monitor/logs/calls
4. **Test on same network**: Easier for initial testing
5. **Verify phone numbers**: Always verify in Twilio console first (trial accounts)

---

## 🎓 What You're Testing

### Technical Components:
- ✅ Twilio SDK integration
- ✅ Video token generation
- ✅ WebRTC connection
- ✅ Phone call initiation
- ✅ Real-time status updates
- ✅ Database updates
- ✅ UI button states

### User Experience:
- ✅ Appointment booking flow
- ✅ Doctor confirmation process
- ✅ Call initiation (both types)
- ✅ In-call experience
- ✅ Call termination
- ✅ Status tracking

---

## 📞 Support

**If you encounter issues:**
1. Check browser console for errors (F12)
2. Check Twilio console logs
3. Verify .env credentials are correct
4. Ensure phone numbers are verified (trial accounts)
5. Test with different browsers

**Twilio Console Links:**
- Dashboard: https://console.twilio.com
- Call Logs: https://console.twilio.com/us1/monitor/logs/calls
- Verified Numbers: https://console.twilio.com/us1/develop/phone-numbers/manage/verified
- Video Logs: https://console.twilio.com/us1/monitor/logs/video

---

**🎉 Success!** If both scenarios work, your Twilio integration is fully functional!
