<!-- src/routes/student/components/WelcomeMessage.svelte -->
<script lang="ts">
  import { name } from "$lib/store/nameStore"; // Import the global name store (assuming .ts now)
  import { studentData } from "$lib/store/student_data"; // Import studentData for pk_studentID
  import { goto } from "$app/navigation";
  import { onMount, onDestroy } from "svelte";
  import { audioStore } from '$lib/store/audio_store';
  import { apiUrl } from '$lib/api_base';

  export let gender: "boy" | "girl"; // Input prop remains boy/girl

  // Compute portrait path for Shenievia depending on selected gender
  $: shenieviaPortrait = gender === 'boy'
  ? '/assets/Level_Walkthrough/shenievia/boy/front/1.png'
  : '/assets/Level_Walkthrough/shenievia/girl/front/1.png';

  // Convert boy/girl to Male/Female for database (not displayed)
  const dbGender: "Male" | "Female" = gender === "boy" ? "Male" : "Female";

  async function updateStudentGender() {
    if (!$studentData) {
      console.error("No student data available");
      return;
    }

    try {
      const response = await fetch(
        apiUrl('update_studentData.php'),
        {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({
            pk_studentID: $studentData.pk_studentID,
            studentGender: dbGender,
          }),
        },
      );

      if (!response.ok)
        throw new Error(`HTTP error! Status: ${response.status}`);

      const result = await response.json();
      if (result.success) {
        studentData.update((data) => ({
          ...data!,
          studentGender: dbGender,
        }));
        await recordAttendance();
      } else {
        console.error("Gender update failed:", result.message);
      }
    } catch (error) {
      console.error("Error updating gender:", error);
    }
  }

  async function recordAttendance() {
    if (!$studentData || !$name) return;

    // Format date to 'YYYY-MM-DD HH:mm:ss' in Asia/Manila timezone
    const attendanceDateTime = new Date()
      .toLocaleString("en-US", {
        timeZone: "Asia/Manila",
        year: "numeric",
        month: "2-digit",
        day: "2-digit",
        hour: "2-digit",
        minute: "2-digit",
        second: "2-digit",
        hour12: false, // Ensures 24-hour format
      })
      .replace(",", ""); // Remove comma for SQL compatibility

    try {
      const response = await fetch(
        apiUrl('record_attendance.php'),
        {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({
            fk_studentID: $studentData.pk_studentID,
            studentName: $name,
            attendanceDateTime: attendanceDateTime,
          }),
        },
      );

      if (!response.ok)
        throw new Error(`HTTP error! Status: ${response.status}`);

      const result = await response.json();
      if (!result.success) {
        console.error("Attendance recording failed:", result.message);
      }
    } catch (error) {
      console.error("Error recording attendance:", error);
    }
  }

  onMount(() => {
    // Start background music on the welcome slide
    try {
      audioStore.playTrack('default');
    } catch (e) {
      // ignore play errors (autoplay policies)
      console.debug('audio play failed', e);
    }

    updateStudentGender();
  });
</script>

<div class="welcome-scene">
  <div class="welcome-inner">
    <div class="portrait">
      <img src={shenieviaPortrait} alt="Shenievia" />
    </div>

    <div class="speech-wrapper">
      <div class="speech-bubble">
        <h2><strong>Welcome, {$name}!</strong></h2>
        <div class="speech-text">
          Hi! I'm <strong>Shenievia</strong>. I'm so happy you joined us. This is <strong>Readville Village</strong> — a friendly place full of lessons and adventures. I'll guide you through the <em>Sari-Sari Store</em>, the <em>Wet Market</em>, and the <em>Plaza</em>. When you're ready, press Continue to begin.
        </div>
      </div>

      <div class="continue-row">
        <button class="continue-btn" on:click={async () => { audioStore.stopAll(); goto('/student/dashboard'); }}>Continue</button>
      </div>
    </div>
  </div>
</div>

<style>
  /* Layout containers */
  .welcome-scene{
    position:relative;
    width:100%;
    max-width:1100px;
    margin:0 auto;
    padding:18px;
    display:flex;
    align-items:center;
    justify-content:center;
    min-height:78vh;
  }

  .welcome-inner{
    display:flex;
    gap:6px;
    align-items:center; /* center portrait and bubble vertically */
    width:100%;
  }

  .portrait{
    flex:0 0 320px;
    display:flex;
    justify-content:flex-end;
    transform: translateY(40px); /* lower the portrait further */
  }

  .portrait img{
    height:480px;
    width:auto;
    image-rendering:pixelated;
    filter:drop-shadow(0 10px 28px rgba(0,0,0,0.28));
    border-radius:8px;
    object-fit:contain;
  }

  .speech-wrapper{
    flex:1;
    max-width:700px;
    display:flex;
    flex-direction:column;
    align-items:flex-start;
    margin-left:-18px; /* overlap slightly with portrait for connected speech */
  }

  /* Speech bubble */
  .speech-bubble{
    position:relative;
    background:linear-gradient(180deg, rgba(248,250,252,0.90), rgba(255,255,255,0.94));
    border:4px solid #065f46;
    padding:20px 22px;
    border-radius:18px;
    box-shadow:0 10px 30px rgba(0,0,0,0.12);
    text-align:justify;
    font-family:'Comic Sans MS', 'Chalkboard', cursive;
    color:#374151;
    line-height:1.45;
    font-size:1.06rem;
    min-height:150px;
  }

  /* bubble tail (outer border triangle) */
  .speech-bubble:before{
    content:"";
    position:absolute;
    left:-34px;
    top:78px; /* moved tail further down to match lowered portrait */
    border-width:16px 20px 16px 0;
    border-style:solid;
    border-color:transparent #065f46 transparent transparent;
  }

  /* bubble tail (inner fill triangle) */
  .speech-bubble:after{
    content:"";
    position:absolute;
    left:-26px;
    top:82px; /* move inner tail down slightly more */
    border-width:12px 16px 12px 0;
    border-style:solid;
    border-color:transparent #ffffff transparent transparent;
    opacity:0.98;
  }

  .speech-bubble h2{
    margin:0 0 10px 0;
    font-size:1.7rem;
    color:#065f46;
  }



  .continue-row{
    margin-top:12px;
    width:100%;
    display:flex;
    justify-content:flex-end;
  }

  /* Continue button subtle bounce */
  @keyframes subtleBounce {
    0% { transform: translateY(0); }
    50% { transform: translateY(-6px); }
    100% { transform: translateY(0); }
  }

  .continue-btn {
    animation: subtleBounce 2.2s ease-in-out infinite;
    transition: transform 0.15s ease, box-shadow 0.15s ease;
    background:#10b981;
    color:white;
    padding:10px 18px;
    border-radius:10px;
    border:none;
    cursor:pointer;
    font-weight:700;
  }

  .continue-btn:hover {
    transform: translateY(-4px) scale(1.02);
    box-shadow: 0 8px 22px rgba(16,185,129,0.18);
  }

  /* Responsive: stack on narrow screens */
  @media (max-width:880px){
    .welcome-inner{flex-direction:column;align-items:center;}
    .portrait{flex:0 0 auto;}
    .portrait img{height:360px;}
    .speech-bubble:before, .speech-bubble:after{display:none;}
    .speech-wrapper{width:100%;padding:0 8px;}
    .speech-bubble{min-height:auto}
  }
</style>
