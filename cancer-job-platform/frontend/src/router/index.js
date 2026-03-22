// frontend/src/router/index.js
import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const routes = [
  { path: '/',          component: () => import('@/views/LandingView.vue') },
  { path: '/login',     component: () => import('@/views/auth/LoginView.vue') },
  { path: '/register',  component: () => import('@/views/auth/RegisterView.vue') },
  {
    path: '/app',
    component: () => import('@/layouts/AppLayout.vue'),
    meta: { requiresAuth: true },
    children: [
      { path: '',           redirect: '/app/chat' },
      { path: 'chat',       component: () => import('@/views/app/ChatView.vue') },
      { path: 'jobs',       component: () => import('@/views/app/JobsView.vue') },
      { path: 'jobs/:id',   component: () => import('@/views/app/JobDetailView.vue') },
      { path: 'community',  component: () => import('@/views/app/CommunityView.vue') },
      { path: 'legal',      component: () => import('@/views/app/LegalView.vue') },
      { path: 'profile',    component: () => import('@/views/app/ProfileView.vue') },
    ],
  },
]

const router = createRouter({
  history: createWebHistory(),
  routes,
})

router.beforeEach((to, _from, next) => {
  const auth = useAuthStore()
  if (to.meta.requiresAuth && !auth.isLoggedIn) {
    next('/login')
  } else {
    next()
  }
})

export default router
