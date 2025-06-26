<template>
  <div class="kiosk-container">
    <button @click="enterFullscreen">Activar Pantalla Completa</button>
    <div class="video-container" ref="videoContainer">
      <video
        ref="videoPlayer"
        class="video-player"
        :src="videoSrc"
        controls
        autoplay
        @ended="onVideoEnded"
      ></video>
      <button v-if="!isFullscreen" @click="enterFullscreen" class="fullscreen-btn">
        Ver en Pantalla Completa
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
const promotion = ref(null)
const videoSrc = ref(
  'http://commondatastorage.googleapis.com/gtv-videos-bucket/sample/BigBuckBunny.mp4',
)
const isFullscreen = ref(false)
const kioskContainer = ref(null)
const enterFullscreen = () => {
  if (kioskContainer.value.requestFullscreen) {
    kioskContainer.value.requestFullscreen()
  } else if (kioskContainer.value.webkitRequestFullscreen) {
    kioskContainer.value.webkitRequestFullscreen()
  } else if (kioskContainer.value.msRequestFullscreen) {
    kioskContainer.value.msRequestFullscreen()
  }
  isFullscreen.value = true
}
const handleFullscreenChange = () => {
  isFullscreen.value = !!document.fullscreenElement
}

onMounted(() => {
  document.addEventListener('fullscreenchange', handleFullscreenChange)

  window.Echo.channel('Promotions').listen('.new-promotion', (e) => {
    promotion.value = e
    alert(e.description)
    setTimeout(() => {
      promotion.value = null
    }, 5000)
  })
})
// // export default {
// //   methods: {
// //     enterFullscreen() {
// //       const videoContainer = this.$refs.videoContainer
// //       if (videoContainer.requestFullscreen) {
// //         videoContainer.requestFullscreen()
// //       } else if (videoContainer.webkitRequestFullscreen) {
// //         videoContainer.webkitRequestFullscreen()
// //       } else if (videoContainer.msRequestFullscreen) {
// //         videoContainer.msRequestFullscreen()
// //       }
// //       this.isFullscreen = true
// //     },
// //     onVideoEnded() {
// //       this.isFullscreen = false
// //       alert('El video ha finalizado.')
// //     },
// //   },
// }
</script>

<style scoped>
.video-container {
  position: relative;
  width: 100%;
  height: 100%;
  background-color: black;
  display: flex;
  align-items: center;
  justify-content: center;
}

.video-player {
  width: 100%;
  height: auto;
}

.fullscreen-btn {
  position: absolute;
  bottom: 10px;
  right: 10px;
  padding: 10px 20px;
  background-color: rgba(0, 0, 0, 0.7);
  color: white;
  border: none;
  border-radius: 5px;
  cursor: pointer;
}

.fullscreen-btn:hover {
  background-color: rgba(255, 255, 255, 0.7);
  color: black;
}

.kiosk-container {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  height: 100vh;
  background-color: #333;
  color: white;
  text-align: center;
}

button {
  margin-top: 20px;
  padding: 10px 20px;
  font-size: 16px;
  cursor: pointer;
  border: none;
  border-radius: 5px;
  background-color: #007bff;
  color: white;
}

button:hover {
  background-color: #0056b3;
}
</style>
