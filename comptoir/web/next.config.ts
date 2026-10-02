import type { NextConfig } from 'next';

const nextConfig: NextConfig = {
  // next dev ne génère pas AGENTS.md ni CLAUDE.md dans web/.
  agentRules: false,
};

export default nextConfig;
