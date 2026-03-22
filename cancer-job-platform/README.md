# 희망일자리 — 암환자 취업 AI 플랫폼
# HopeJob — AI Employment Platform for Cancer Patients

> **공익 선언 / Public Interest Declaration**
>
> 이 프로젝트는 암 투병 중이거나 완치 후 사회 복귀를 준비하는 분들의 경제적 자립과 삶의 질 향상을 위해 개발되었습니다. 상업적 이익보다 사회적 가치를 우선하며, 누구나 자유롭게 사용·수정·배포할 수 있는 오픈소스로 운영됩니다.
>
> This project was built to support the economic independence and quality of life of people living with or recovering from cancer. It prioritizes social value over commercial gain and is maintained as open-source software — free to use, modify, and distribute.

[![License: MIT](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)
[![Public Interest](https://img.shields.io/badge/Purpose-Public%20Interest-brightgreen)]()
[![Language](https://img.shields.io/badge/Lang-KO%20%7C%20EN-blue)]()

---

## 목차 / Table of Contents

1. [프로젝트 소개 / About](#프로젝트-소개--about)
2. [공익적 활용 원칙 / Public Interest Principles](#공익적-활용-원칙--public-interest-principles)
3. [기술 스택 / Tech Stack](#기술-스택--tech-stack)
4. [아키텍처 / Architecture](#아키텍처--architecture)
5. [주요 기능 / Features](#주요-기능--features)
6. [보안 설계 / Security](#보안-설계--security)
7. [로컬 실행 / Local Setup](#로컬-실행--local-setup)
8. [데이터베이스 스키마 / Database Schema](#데이터베이스-스키마--database-schema)
9. [다음 개발 단계 / Roadmap](#다음-개발-단계--roadmap)
10. [기여 방법 / Contributing](#기여-방법--contributing)

---

## 프로젝트 소개 / About

**한국어**
한국에는 매년 약 25만 명의 신규 암 환자가 발생하며, 치료 후 사회 복귀 과정에서 취업 차별·정보 부족·심리적 어려움을 겪는 분들이 많습니다. 희망일자리는 AI 기술을 활용해 이 격차를 줄이고, 암 경험자가 존엄성을 유지하며 경제 활동에 참여할 수 있도록 돕는 플랫폼입니다.

**English**
Approximately 250,000 new cancer cases are diagnosed annually in South Korea. Many survivors face employment discrimination, lack of information, and psychological barriers when re-entering the workforce. HopeJob uses AI to bridge this gap — empowering cancer patients and survivors to participate in economic life with dignity.

PostVisit.ai 아키텍처에서 영감을 받아 설계되었습니다.
*(Inspired by the architecture of PostVisit.ai.)*

---

## 공익적 활용 원칙 / Public Interest Principles

**한국어**

이 소프트웨어를 사용·배포·수정할 때는 아래 원칙을 준수할 것을 권장합니다.

1. **비차별 원칙** — 암 종류, 치료 단계, 나이, 성별, 장애 여부와 무관하게 모든 사용자를 평등하게 대우해야 합니다.
2. **데이터 최소 수집** — 서비스 제공에 필요한 최소한의 개인정보만 수집하며, 건강 정보를 제3자에게 판매·제공하지 않습니다.
3. **접근성 보장** — 경제적 능력에 관계없이 핵심 기능은 무료로 제공되어야 합니다.
4. **투명한 AI** — AI가 제공하는 정보의 한계(의학적 조언 불가, 법적 조언 불가)를 사용자에게 명확히 고지해야 합니다.
5. **커뮤니티 환원** — 이 코드를 기반으로 수익을 창출할 경우, 수익의 일부를 암 환자 지원 단체에 기부할 것을 권장합니다.

**English**

When using, distributing, or modifying this software, we encourage adherence to the following principles:

1. **Non-discrimination** — Treat all users equally regardless of cancer type, treatment stage, age, gender, or disability status.
2. **Data minimization** — Collect only the minimum personal data necessary for the service; never sell or share health information with third parties.
3. **Accessibility** — Core features should remain free regardless of a user's financial situation.
4. **AI transparency** — Clearly communicate the limitations of AI-provided information (no medical advice, no legal advice) to every user.
5. **Community reinvestment** — If generating revenue from this codebase, consider donating a portion of proceeds to cancer patient support organizations.

---

## 기술 스택 / Tech Stack

```
희망일자리 / HopeJob
├── backend/    Laravel 12 (PHP 8.4)  — REST API + AI 로직 / REST API + AI Logic
├── frontend/   Vue 3 + Vite          — SPA
└── infra/      Docker Compose        — 로컬 개발 환경 / Local Dev Environment
```

| 레이어 / Layer | 기술 / Technology |
|---|---|
| AI 엔진 / AI Engine | Claude claude-sonnet-4-20250514 (SSE 스트리밍 / SSE Streaming) |
| 백엔드 API / Backend API | Laravel 12, Sanctum 인증 / Auth, Rate Limiting |
| 프론트엔드 / Frontend | Vue 3, Pinia, Vue Router, marked.js |
| DB | PostgreSQL 17 (UUID PK, jsonb) |
| 인증 / Authentication | Laravel Sanctum (Bearer Token) |
| 감사 로그 / Audit Log | 모든 AI 요청 IP·사용자·리소스 기록 / All AI requests logged with IP, user, resource |

---

## 아키텍처 / Architecture

### 5레이어 AI 컨텍스트 조립 / 5-Layer AI Context Assembly

`ChatController`가 Claude에게 전달하는 시스템 프롬프트는 5개 레이어로 구성됩니다.
*The system prompt delivered to Claude by `ChatController` is composed of 5 layers:*

```
Layer 1 — 사용자 프로필 / User Profile
          (암 종류, 치료 단계, 근무 가능 형태, 희망 직종)
          (Cancer type, treatment stage, work capacity, desired job types)

Layer 2 — 매칭 일자리 TOP 5 / Top 5 Matched Jobs
          (프로필 기반 필터링 + AI 매칭 점수)
          (Profile-based filtering + AI match score)

Layer 3 — 관련 법적 제도 / Relevant Legal Guides
          (장애인 고용촉진법, 산재보험, 지원금)
          (Disability Employment Act, workers' comp, subsidies)

Layer 4 — 대화 히스토리 / Conversation History
          (최근 10턴 / Last 10 turns)

Layer 5 — 역할 지침 / Role Instructions
          (공감 → 정보 제공, 의료·법률 면책)
          (Empathy first → information, medical & legal disclaimers)
```

### 요청 흐름 / Request Flow

```
Vue SPA
  │  POST /api/chat  (Bearer Token)
  ▼
ChatController
  ├── UserProfile 조회 / lookup
  ├── JobContextService   → 매칭 공고 TOP 5 / top 5 matched jobs
  ├── LegalContextService → 관련 제도 / relevant guides
  ├── ChatMessage 히스토리 / history (last 10)
  └── ClaudeService.streamChat()
        │  SSE token stream
        ▼
      Vue (text/event-stream)
        └── 토큰 단위 렌더링 / token-by-token rendering
```

---

## 주요 기능 / Features

### AI 상담 / AI Counseling (`ChatView`)
- Claude claude-sonnet-4-20250514 **SSE 스트리밍** — 토큰 단위 실시간 출력 / real-time token-by-token output
- 멀티턴 히스토리 유지 (DB 저장) / Multi-turn history persistence
- 마크다운 렌더링 (marked.js + DOMPurify) / Markdown rendering with XSS sanitization
- 퀵 질문 칩 / Quick-prompt chips

### 취업 매칭 / Job Matching (`JobsView`)
- 프로필 기반 필터링 / Profile-based filtering (재택·파트타임·장애인 우대·유연근무 / remote, part-time, disability-preferred, flexible hours)
- AI 매칭 점수 0–100 / AI match score — 스킬 오버랩, 근무 형태, 지역 / skill overlap, work type, region
- 개별 공고 AI 설명 / Per-job AI explanation ("왜 이 일자리가 맞나요?" / "Why does this job fit you?")
- 관심 공고 저장 / Save jobs

### 커뮤니티 / Community
- 카테고리별 게시판 / Category boards (경험 나눔·질문·멘토 신청·취업 팁 / stories, Q&A, mentor requests, job tips)
- 멘토·멘티 매칭 (동일 암 종류 우선) / Mentor-mentee matching (same cancer type prioritized)
- 멘토 답변 배지 / Mentor reply badge

### 법적 지원제도 / Legal Guides
- 장애인 고용촉진법, 산재보험, 암환자 지원금 DB / Disability Employment Act, workers' compensation, cancer subsidies
- 카테고리 검색 / Category search
- AI 상담 연동 — 상담 중 관련 제도 자동 인용 / Auto-cited during AI counseling sessions

---

## 보안 설계 / Security

PostVisit.ai의 HIPAA-inspired 설계를 참고했습니다.
*Inspired by PostVisit.ai's HIPAA-inspired design.*

| 항목 / Item | 설명 / Description |
|---|---|
| **인증 / Auth** | 모든 API 엔드포인트 Sanctum 필수 / All endpoints require Sanctum Bearer token |
| **감사 로그 / Audit Log** | `audit_logs` 테이블 — 사용자·IP·액션·리소스 기록 / user, IP, action, resource |
| **Rate Limiting** | Laravel throttle 미들웨어, AI 엔드포인트 별도 제한 / Separate throttle for AI endpoints |
| **PHI 최소화 / PHI Minimization** | `toPublicArray()`로 AI 컨텍스트에 민감 정보 미전송 / Sensitive fields excluded from AI context |
| **입력 검증 / Input Validation** | 메시지 최대 2,000자 / Max 2,000 characters per message |
| **XSS 방어 / XSS Defense** | DOMPurify로 AI 응답 마크다운 새니타이징 / AI markdown output sanitized |

---

## 로컬 실행 / Local Setup

### 사전 요구사항 / Prerequisites
- PHP 8.4+, Composer 2+
- Node.js 20+, npm 10+
- PostgreSQL 17+

### 백엔드 / Backend
```bash
cd backend
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan serve
```

### 프론트엔드 / Frontend
```bash
cd frontend
npm install
npm run dev
```

### 환경 변수 / Environment Variables

`.env`에 추가 / Add to `.env`:
```env
ANTHROPIC_API_KEY=sk-ant-...
DB_CONNECTION=pgsql
DB_DATABASE=hopejob
DB_USERNAME=postgres
DB_PASSWORD=your_password
```

---

## 데이터베이스 스키마 / Database Schema

```
users
  └── user_profiles (1:1)       암 종류, 치료 단계, 근무 가능 형태
                                 Cancer type, treatment stage, work capacity

job_listings
  └── saved_jobs                 유저별 관심 공고 / Saved jobs per user

chat_messages                    멀티턴 히스토리 / Multi-turn history

community_posts
  └── community_replies

legal_guides                     지원제도 DB / Support program database

audit_logs                       보안 감사 트레일 / Security audit trail
```

---

## 다음 개발 단계 / Roadmap

- [ ] 사람인·잡코리아 API 연동 (실시간 공고 크롤링) / Saramin & Jobkorea API integration
- [ ] 음성 상담 (Whisper → Claude → TTS) / Voice counseling
- [ ] 알림 시스템 (새 공고, 멘토 답변) / Notification system
- [ ] 관리자 대시보드 / Admin dashboard
- [ ] PWA / Mobile app
- [ ] 다국어 지원 (영어, 일본어) / Multilingual support (EN, JA)
- [ ] 공공 데이터 연동 (고용24, 복지로) / Public data integration (Korea Employment Portal, Bokjiro)

---

## 기여 방법 / Contributing

**한국어**
이 프로젝트는 암 환자 및 그 가족, 의료·복지·기술 분야 전문가, 그리고 공익적 기술 개발에 관심 있는 모든 분의 기여를 환영합니다. 버그 수정, 새 기능 제안, 번역, 콘텐츠(법적 지원제도 정보 업데이트) 등 어떤 형태의 기여도 소중합니다.

**English**
This project welcomes contributions from cancer patients and their families, professionals in healthcare, social welfare, and technology, and anyone who cares about purpose-driven software. Bug fixes, feature proposals, translations, and content contributions (e.g., updating legal guide data) are all valued equally.

```bash
# 1. Fork → 2. 브랜치 생성 / Create branch
git checkout -b feature/your-feature

# 3. 커밋 / Commit
git commit -m "feat: describe your change"

# 4. PR 생성 / Open Pull Request
```

---

## 라이선스 / License

MIT License — 자유롭게 사용, 수정, 배포하되 공익 목적에 부합하게 활용해 주세요.
*Free to use, modify, and distribute — please use in a manner consistent with the public interest principles above.*

---

*희망이 있는 곳에 일자리가 있습니다. / Where there is hope, there is work.*
