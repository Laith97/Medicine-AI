const fs = require('fs');
const path = require('path');
const sharp = require('sharp');

const iconsDir = path.join(__dirname, '..', 'public', 'icons');

// Ensure icons directory exists
if (!fs.existsSync(iconsDir)) {
  fs.mkdirSync(iconsDir, { recursive: true });
}

// Doctor icon SVG - stethoscope with medical cross on blue rounded square background
function generateDoctorIcon() {
  // SVG with blue rounded rectangle background (#0EA5E9) and white stethoscope
  const svg = `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64" width="64" height="64">
  <!-- Blue rounded square background -->
  <rect x="4" y="4" width="56" height="56" rx="12" ry="12" fill="#0EA5E9"/>
  <!-- White stethoscope symbol -->
  <circle cx="32" cy="14" r="7" fill="none" stroke="white" stroke-width="2.5"/>
  <path d="M25 21 L25 36 Q25 44 32 44 Q39 44 39 36 L39 21" fill="none" stroke="white" stroke-width="2.5" stroke-linecap="round"/>
  <circle cx="32" cy="48" r="4" fill="white"/>
  <path d="M28 48 L19 52" stroke="white" stroke-width="2.5" stroke-linecap="round"/>
  <path d="M36 48 L45 52" stroke="white" stroke-width="2.5" stroke-linecap="round"/>
</svg>`;

  fs.writeFileSync(path.join(iconsDir, 'doctor-icon.svg'), svg);
  console.log('Generated doctor-icon.svg');

  // Generate PNG files using sharp
  const sizes = [192, 512];
  const svgBuffer = Buffer.from(svg);

  Promise.all(sizes.map(size => {
    return sharp(svgBuffer)
      .resize(size, size)
      .png()
      .toFile(path.join(iconsDir, `doctor-icon-${size}.png`))
      .then(() => console.log(`Generated doctor-icon-${size}.png`));
  })).then(() => {
    console.log('All doctor icon PNG files generated!');
  }).catch(err => {
    console.error('Error generating PNG files:', err);
  });
}

// Patient icon SVG - patient figure/person
function generatePatientIcon() {
  const svg = `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64" width="64" height="64">
  <!-- Patient icon - person with medical symbol -->
  <circle cx="32" cy="16" r="10" fill="none" stroke="#059669" stroke-width="3"/>
  <path d="M16 52 Q16 34 32 34 Q48 34 48 52" fill="none" stroke="#059669" stroke-width="3" stroke-linecap="round"/>
  <!-- Medical cross on chest -->
  <rect x="30" y="38" width="4" height="10" rx="1" fill="#dc2626"/>
  <rect x="27" y="41" width="10" height="4" rx="1" fill="#dc2626"/>
</svg>`;

  fs.writeFileSync(path.join(iconsDir, 'patient-icon.svg'), svg);
  console.log('Generated patient-icon.svg');
}

// Run doctor icon generation (Task 1)
generateDoctorIcon();

// Patient icon generation will be called in Task 2
// generatePatientIcon();

console.log('Icon generation complete!');
