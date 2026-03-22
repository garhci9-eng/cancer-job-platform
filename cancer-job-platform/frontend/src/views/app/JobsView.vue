<template>
  <div class="jobs-page">
    <div class="page-header">
      <h1>맞춤 일자리</h1>
      <p>치료와 병행 가능한 일자리를 AI가 분석해 매칭합니다</p>
    </div>

    <!-- Filters -->
    <div class="filter-bar">
      <button
        v-for="f in filters"
        :key="f.value"
        class="filter-btn"
        :class="{ active: activeFilter === f.value }"
        @click="activeFilter = f.value; fetchJobs()"
      >{{ f.label }}</button>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="loading-grid">
      <div v-for="i in 6" :key="i" class="skeleton-card"></div>
    </div>

    <!-- Job cards -->
    <div v-else class="job-grid">
      <div
        v-for="job in jobs"
        :key="job.id"
        class="job-card"
        @click="$router.push(`/app/jobs/${job.id}`)"
      >
        <div class="card-top">
          <div class="job-title">{{ job.title }}</div>
          <div class="match-badge" :style="matchColor(job.match_score)">
            {{ job.match_score }}% 매칭
          </div>
        </div>
        <div class="company">{{ job.company }}</div>

        <div class="tags">
          <span class="tag type">{{ typeLabel(job.job_type) }}</span>
          <span class="tag emp">{{ empLabel(job.employment_type) }}</span>
          <span v-if="job.disability_preferred" class="tag disability">장애인 우대</span>
          <span v-if="job.flexible_hours" class="tag flex">유연근무</span>
        </div>

        <div class="location">📍 {{ job.location }}</div>

        <button class="save-btn" @click.stop="toggleSave(job)">
          {{ savedIds.has(job.id) ? '♥ 저장됨' : '♡ 저장' }}
        </button>
      </div>
    </div>

    <!-- Empty state -->
    <div v-if="!loading && jobs.length === 0" class="empty">
      <div>🔍</div>
      <p>조건에 맞는 일자리가 없어요.<br/>프로필을 업데이트하면 더 많은 공고를 볼 수 있어요.</p>
      <router-link to="/app/profile" class="profile-link">프로필 업데이트 →</router-link>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import api from '@/services/api'

const jobs        = ref([])
const loading     = ref(true)
const activeFilter = ref('all')
const savedIds    = ref(new Set())

const filters = [
  { label: '전체',     value: 'all' },
  { label: '재택근무', value: 'remote' },
  { label: '파트타임', value: 'part_time' },
  { label: '유연근무', value: 'flexible' },
  { label: '장애인 우대', value: 'disability' },
]

onMounted(async () => {
  await Promise.all([fetchJobs(), fetchSaved()])
})

async function fetchJobs() {
  loading.value = true
  const params = activeFilter.value !== 'all' ? { filter: activeFilter.value } : {}
  const { data } = await api.get('/jobs', { params })
  jobs.value = data.data
  loading.value = false
}

async function fetchSaved() {
  const { data } = await api.get('/jobs/saved')
  savedIds.value = new Set(data.jobs.map(j => j.id))
}

async function toggleSave(job) {
  await api.post(`/jobs/${job.id}/save`)
  if (savedIds.value.has(job.id)) {
    savedIds.value.delete(job.id)
  } else {
    savedIds.value.add(job.id)
  }
}

function matchColor(score) {
  if (score >= 80) return { background: '#e4f7ee', color: '#1a7a45' }
  if (score >= 60) return { background: '#fef9e7', color: '#946c00' }
  return { background: '#f5f5f5', color: '#666' }
}

function typeLabel(t) {
  return { remote: '재택', hybrid: '하이브리드', onsite: '현장' }[t] ?? t
}

function empLabel(t) {
  return { full_time: '정규직', part_time: '파트타임', contract: '계약직', freelance: '프리랜서' }[t] ?? t
}
</script>

<style scoped>
.jobs-page { padding: 24px 20px; max-width: 900px; margin: 0 auto; }
.page-header h1 { font-size: 22px; font-weight: 700; color: #1a1a1a; margin: 0 0 4px; }
.page-header p  { font-size: 13px; color: #888; margin: 0 0 20px; }

.filter-bar {
  display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 20px;
}
.filter-btn {
  padding: 7px 16px; border-radius: 20px; font-size: 13px; font-weight: 500;
  border: 1px solid #e0e0e0; background: #fff; color: #555; cursor: pointer;
  transition: all .15s;
  &.active { background: #34c77b; color: #fff; border-color: #34c77b; }
  &:hover:not(.active) { border-color: #34c77b; color: #34c77b; }
}

.job-grid {
  display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 16px;
}
.loading-grid { @extend .job-grid; }
.skeleton-card {
  height: 180px; border-radius: 16px;
  background: linear-gradient(90deg, #f0f0f0 25%, #e8e8e8 50%, #f0f0f0 75%);
  background-size: 200% 100%;
  animation: shimmer 1.4s infinite;
}
@keyframes shimmer { 0%{background-position:200%} 100%{background-position:-200%} }

.job-card {
  background: #fff; border-radius: 16px; padding: 18px;
  border: 1px solid #eef5f1; cursor: pointer;
  transition: transform .15s, box-shadow .15s;
  display: flex; flex-direction: column; gap: 8px;
  &:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(0,0,0,.08); }
}
.card-top { display: flex; justify-content: space-between; align-items: flex-start; gap: 8px; }
.job-title { font-size: 15px; font-weight: 700; color: #1a1a1a; line-height: 1.3; }
.match-badge { font-size: 11px; font-weight: 700; padding: 4px 10px; border-radius: 20px; white-space: nowrap; }
.company  { font-size: 13px; color: #666; }
.location { font-size: 12px; color: #999; }

.tags { display: flex; flex-wrap: wrap; gap: 6px; }
.tag {
  font-size: 11px; padding: 3px 10px; border-radius: 20px;
  &.type       { background: #e8f4fd; color: #185fa5; }
  &.emp        { background: #f0f0f0; color: #555; }
  &.disability { background: #fff3e0; color: #e65100; }
  &.flex       { background: #f3e5f5; color: #6a1b9a; }
}

.save-btn {
  margin-top: auto; background: none; border: 1px solid #e0e0e0; border-radius: 8px;
  padding: 6px 12px; font-size: 12px; color: #666; cursor: pointer;
  transition: all .15s; align-self: flex-start;
  &:hover { border-color: #e57373; color: #e57373; }
}

.empty { text-align: center; padding: 60px 20px; color: #888; font-size: 14px; line-height: 1.7; }
.empty div { font-size: 36px; margin-bottom: 12px; }
.profile-link {
  display: inline-block; margin-top: 12px; color: #34c77b; font-weight: 600; text-decoration: none;
  &:hover { text-decoration: underline; }
}
</style>
