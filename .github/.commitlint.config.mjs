// More info: https://github.com/wayofdev/npm-shareable-configs/blob/master/packages/commitlint-config/src/index.js
const automaticCommitPattern = /^chore\(release\):.*\[skip ci]/

export default {
    extends: ['@commitlint/config-conventional'],
    /*
      This resolves a linting conflict between commitlint's body-max-line-length
      due to @semantic-release/git putting release notes in the commit body
      https://github.com/semantic-release/git/issues/331
    */
    ignores: [(commitMessage) => automaticCommitPattern.test(commitMessage)],
    rules: {
        'body-leading-blank': [1, 'always'],
        'body-max-line-length': [2, 'always', 100],
        'footer-leading-blank': [1, 'always'],
        'footer-max-line-length': [2, 'always', 100],
        'header-max-length': [2, 'always', 100],
        'scope-case': [2, 'always', 'lower-case'],
        'subject-case': [2, 'never', ['sentence-case', 'start-case', 'pascal-case', 'upper-case']],
        'subject-empty': [2, 'never'],
        'subject-full-stop': [2, 'never', '.'],
        'type-case': [2, 'always', 'lower-case'],
        'type-empty': [2, 'never'],
        'type-enum': [
            2,
            'always',
            [
                'feat',
                'fix',
                'perf',
                'docs',
                'style',
                'deps',
                'refactor',
                'ci',
                'test',
                'revert',
                'build',
                'chore',
            ],
        ],
    },
}
