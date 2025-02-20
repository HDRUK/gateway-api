module.exports = {

    branches: ["main"],

    plugins: [

      "@semantic-release/commit-analyzer",

      [

        "@semantic-release/release-notes-generator",

        {

          preset: "conventionalcommits",

          presetConfig: {

            types: [

              { type: "feat", section: "✨ Features" },

              { type: "fix", section: "🐛 Bug Fixes" },

              { type: "perf", section: "⚡ Performance Improvements" },

              { type: "docs", section: "📖 Documentation", hidden: false },

              { type: "chore", section: "🔧 Maintenance", hidden: true },

            ],

            issuePrefixes: ["GAT", "TPD", "OS"], // Jira prefixes

            issueUrlFormat: process.env.JIRA_URL + "{{prefix}}{{id}}"

          }

        }

      ],

      [

        "@semantic-release/changelog",

        {

          changelogFile: "CHANGELOG.md",

        }

      ],

      [

        "@semantic-release/github",

        {

          successComment: false,

          failComment: false,

        }

      ],

      [

        "@semantic-release/exec",

        {

          prepareCmd: "node updateVersions.js ${nextRelease.version} && git add chart/gateway-api/Chart.yaml package.json"

        }

      ],

      [

        "@semantic-release/git",

        {

          assets: [

            "package.json",

            "package-lock.json",

            "CHANGELOG.md",

            "chart/gateway-api/Chart.yaml"

          ],

          message: "chore(release): ${nextRelease.version} [skip ci]\n\n${nextRelease.notes}"

        }

      ]

    ]

  };