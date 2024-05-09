#!/bin/bash
set -ex

cd $PATCHDEMO/wikis/$NAME/$REPO

git fetch origin $REF

: <<'COMMENT'
Apply $HASH and its parent commits up to $BASE on top of current HEAD, skipping already applied
commits.

Consider the following situation: we've been asked to apply the patches on the branch 'changes',
which is based on 'master', onto the 'REL1_XX' branch.

(I hope you like ASCII-art inspired by Git's man pages)

 ---A---B---C---D---E         master   ($BASE)
     \           \
      \           X---Y---Z   changes  ($HASH)
       \
        P---Q                 REL1_XX  (HEAD)

We need to apply commits X through Z (reachable only from `changes`), but not B through D (also
reachable from `master`), to achieve:

 ---A---B---C---D---E         master
     \           \
      \           X---Y---Z   changes
       \
        P---Q---X'--Y'--Z'    HEAD

This could be done as follows:
    git cherry-pick master..changes
or equivalently:
    git cherry-pick changes ^master
(this is also basically the same as:)
    git rebase --onto HEAD master changes

Complications arise if we already applied some of X, Y and Z to HEAD, perhaps in a previous run
of applypatch.sh, or because some of them have been already rebased and merged.

 ---A---B---C---D---E         master
     \           \
      \           X---Y---Z   changes
       \
        P---Q---X'--Y'        HEAD

[To reproduce this kind of tree locally, you can run:
    git init
    touch FILE1 FILE2
    git add FILE1 FILE2
    git commit -m INITIAL
    echo A > FILE1 && git commit FILE1 -m A
    echo B > FILE1 && git commit FILE1 -m B
    echo C > FILE1 && git commit FILE1 -m C
    echo D > FILE1 && git commit FILE1 -m D
    echo E > FILE1 && git commit FILE1 -m E
    git checkout @~1 -b changes
    echo X > FILE2 && git commit FILE2 -m X
    echo Y > FILE2 && git commit FILE2 -m Y
    echo Z > FILE2 && git commit FILE2 -m Z
    git checkout @~6 -b REL1_XX
    echo P > FILE1 && git commit FILE1 -m P
    echo Q > FILE1 && git commit FILE1 -m Q
    git checkout REL1_XX~0
    git cherry-pick changes~3..changes~1
]

Here we only need to apply Z. Doing it in the naive way described above may work sometimes, as
Git will often detect when applying e.g. X directly on top of X' that all the changes are already
there and skip that commit, but applying X on top of Y' will usually fail with merge conflicts if
both commits touch the same files. How to detect that X and Y were already applied and only apply
Z, ideally without walking the tree ourselves?

Git has a mechanism to detect such basically identical commits, and one of the ways to access it is
`git log --cherry-mark`, which can be used only when listing a "symmetric difference" of branches
`A...B` (that is, commits reachable from either the A or B branch, but not common to both of them),
and will highlight commits that are identical between the two branches. Adding a few options to
visualize it, you might get something like:

    $ git log --oneline --graph --boundary --cherry-mark HEAD...changes
    = 98d0751 (HEAD) Y
    = a2a9696 X
    * b09886c (REL1_XX) Q
    * ac284a5 P
    | * 009c40d (changes) Z
    | = 8d0ca95 Y
    | = ef773e2 X
    | * 593bdd9 D
    | * eb975f6 C
    | * 9d9a92b B
    |/
    o 85307dc A

This shows the commits reachable from both branches, and shows that commits X and Y on both of
them are identical.

Now how to apply the commits we want? Instead of `--cherry-mark`, we can use `--cherry-pick`
to omit the identical commits from the list, and add `--left-only` or `--right-only` to show
only one of the sides of the symmetric difference:

    $ git log --oneline --cherry-pick --right-only HEAD...changes
    009c40d (changes) Z
    593bdd9 D
    eb975f6 C
    9d9a92b B

Almost there… It shows that Z is the only "new" commit to apply, but it also lists commits
B through D from the master branch, which we want to exclude. To do that, we can combine this with
the previous technique (remember how `^` can be used to exclude commits reachable from a branch):

    $ git log --oneline --cherry-pick --right-only HEAD...changes ^master
    009c40d (changes) Z

(There are many other, simpler ways to specify the same set of commits as `HEAD...changes ^master`,
but only this version will work with `--cherry-pick`.)

We could swap `git log --oneline` to `git rev-list` to just get a list of commit hashes, and pass
that to `git cherry-pick --stdin`. But `git cherry-pick` also directly accepts all of the ways of
specifying commits we used, so we can do everything in one command:

    git cherry-pick --cherry-pick --right-only HEAD...changes ^master

(Yep, we pass the option `--cherry-pick` to the command `git cherry-pick`, nothing to see here.)

I wrote this up so that you, dear reader, also know what I went through to write this one line.
The same mechanism is normally used by `git rebase` to drop commits that were already merged
upstream, but it all gets disabled if you use `--onto`, so I had to figure this out myself.

There is one last stupid obstacle: `cherry-pick` will exit with "error: empty commit set passed"
if all of the commits we want were already applied. We check the output of `rev-list` first
to avoid this error.
COMMENT

git rev-list --cherry-pick --right-only HEAD...$HASH ^$BASE --count | grep ^0$ || \
  git -c user.email="patchdemo@example.com" -c user.name="Patch Demo" cherry-pick --cherry-pick --right-only HEAD...$HASH ^$BASE
