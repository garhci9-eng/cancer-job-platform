<template>
  <div class="app-shell">
    <!-- Sidebar -->
    <nav class="sidebar">
      <div class="logo">
        <span class="logo-icon">🌱</span>
        <span class="logo-text">희망일자리</span>
      </div>

      <div class="nav-links">
        <router-link
          v-for="item in navItems"
          :key="item.to"
          :to="item.to"
          class="nav-item"
          active-class="active"
        >
          <span class="nav-icon">{{ item.icon }}</span>
          <span class="nav-label">{{ item.label }}</span>
        </router-link>
      </div>

      <div class="sidebar-bottom">
        <router-link to="/app/profile" class="nav-item" active-class="active">
          <span class="nav-icon">👤</span>
          <span class="nav-label">내 프로필</span>
        </router-link>
        <button class="logout-btn" @click="authStore.logout(); $router.push('/')">
          <span class="nav-icon">↩</span>
          <span class="nav-label">로그아웃</span>
        </button>
      </div>
    </nav>

    <!-- Mobile bottom nav -->
    <nav class="bottom-nav">
      <router-link v-for="item in navItems" :key="item.to" :to="item.to" class="bot-item" active-class="active">
        <span>{{ item.icon }}</span>
        <span class="bot-label">{{ item.label }}</span>
      </router-link>
    </nav>

    <!-- Main content -->
    <main class="main-content">
      <router-view />
    </main>
  </div>
</template>

<script setup>
import { useAuthStore } from '@/stores/auth'
const authStore = useAuthStore()

const navItems = [
  { to: '/app/chat',      icon: '💬', label: 'AI 상담' },
  { to: '/app/jobs',      icon: '💼', label: '일자리' },
  { to: '/app/community', icon: '🤝', label: '커뮤니티' },
  { to: '/app/legal',     icon: '📋', label: '지원제도' },
]
</script>

<style scoped>
.app-shell {
  display: flex; height: 100vh; overflow: hidden;
  background: #f8faf9;
  font-family: 'Noto Sans KR', sans-serif;
}

.sidebar {
  width: 220px; flex-shrink: 0; background: #fff;
  border-right: 1px solid #eef5f1;
  display: flex; flex-direction: column; padding: 20px 12px;
}
.logo {
  display: flex; align-items: center; gap: 10px;
  padding: 0 8px 24px;
}
.logo-icon { font-size: 24px; }
.logo-text  { font-size: 17px; font-weight: 800; color: #1a7a45; letter-spacing: -.5px; }

.nav-links   { flex: 1; display: flex; flex-direction: column; gap: 4px; }
.sidebar-bottom { display: flex; flex-direction: column; gap: 4px; padding-top: 12px; border-top: 1px solid #f0f0f0; }

.nav-item, .logout-btn {
  display: flex; align-items: center; gap: 10px;
  padding: 10px 12px; border-radius: 10px; font-size: 14px;
  color: #555; text-decoration: none; transition: all .15s;
  border: none; background: none; cursor: pointer; width: 100%; text-align: left;
  &:hover { background: #f4fdf8; color: #1a7a45; }
  &.active { background: #e4f7ee; color: #1a7a45; font-weight: 600; }
}
.nav-icon  { font-size: 18px; width: 24px; text-align: center; }

.main-content { flex: 1; overflow-y: auto; }

/* Mobile */
.bottom-nav { display: none; }
@media (max-width: 640px) {
  .sidebar     { display: none; }
  .bottom-nav  {
    display: flex; position: fixed; bottom: 0; left: 0; right: 0; z-index: 100;
    background: #fff; border-top: 1px solid #eef5f1;
    padding: 6px 0 env(safe-area-inset-bottom);
  }
  .bot-item {
    flex: 1; display: flex; flex-direction: column; align-items: center; gap: 2px;
    text-decoration: none; font-size: 20px; color: #aaa; padding: 4px;
    &.active { color: #34c77b; }
  }
  .bot-label { font-size: 10px; }
  .main-content { padding-bottom: 64px; }
}
</style>
