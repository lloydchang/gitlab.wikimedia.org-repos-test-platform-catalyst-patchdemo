<img src="https://gitlab.wikimedia.org/repos/test-platform/catalyst/patchdemo/-/raw/master/images/icon.svg" alt="Patch demo" width="50" valign="middle"> &nbsp; <img src="https://gitlab.wikimedia.org/repos/test-platform/catalyst/patchdemo/-/raw/master/images/wordmark.svg" alt="Patch demo" width="160" valign="middle">

---

With **Patch demo** you can quickly spin up a MediaWiki instance running a particular patch from Wikimedia Gerrit. (An idea was first described in [T76245](https://phabricator.wikimedia.org/T76245).)

A public instance is available at <https://patchdemo.wmcloud.org/>. You will need a Wikimedia account to use it.

This project is not secure. You should only install it in disposable virtual machines, and maybe have some monitoring in place in case someone starts mining bitcoin on them.

While a token effort has been made to avoid remote code execution vulnerabilities, the whole point of the project is to allow your users to execute arbitrary code on the demo wikis, and the wikis are not isolated.

Features
----
* Create a public wiki with [bundled extensions/skins](./repository-lists/all.txt)
* Use a specific release or WMF version
* Apply any number of patches to MediaWiki, extensions or skins
* Require that patches have V+2 review (token security effort)

Limitations
----
* Runs MediaWiki only – no RESTBase and other fancy stuff

## Local development

### On Local Kubernetes Cluster

#### Prerequisites

1. install [golang 1.23](https://go.dev/doc/install)
1. (optional: for debugging) install delve with `go install github.com/go-delve/delve/cmd/dlv@latest`
1. install [skaffold](https://skaffold.dev/docs/install/)
1. install jg (apt or brew installable)
1. install [minikube](https://minikube.sigs.k8s.io/docs/start/?arch=%2Flinux%2Fx86-64%2Fstable%2Fbinary+download)
1. install [kubectl](https://kubernetes.io/docs/tasks/tools/)
1. install [helm](https://helm.sh/docs/intro/install/)
1. (optional: for cluster visibility) install [k9s](https://k9scli.io/topics/install/)

#### Running

Run:

```
./dev
```

from the repo directory and wait for the deployment to become stable.

`dev` does the following:

- ensures minikube is started with the ingress addon
- builds a development version of the patchdemo container images for local use with minikube
- configures and deploys the patchdemo helm chart on minikube
  - adds minikubes kubeconfig to the chart values
  - sets up ingress from your local machine into the cluster
    - open up `_skaffold_env.yaml` `ingress.hostname` to see the URL you can point curl, postman, or your browser to
- forwards the delve debug port to port 40000 on the local machine

#### Visiting local patchdemo

Find out your minikube's IP with `minikube ip`. You can then visit patchdemo at `http://patchdemo.$(minikube ip).nip.io`.

## Catalyst design diagram

The following diagram shows the three layers Catalyst is structured around:

![alt arch](docs/Catalyst.png "Catalyst")

FAQ
----
### Can you delete a wiki when you are done with it?

Yes. For any wiki you create, you will see a `Delete` link in the `Action` column of the table of previously generated wikis on the main page. We advise you to delete the wikis you create when you are finished with them and/or when the patch you created the wiki to test is merged.

### How long do the Patch demo wiki instances last for?

There is no definitive time after which wikis will automatically be deleted. With this said, we make no guarantees about how long they will continue to exist. A Patch demo wiki you've created could be deleted if we need to free up disk space to create space for new ones.

### Can Patch demo wikis be named?

Wikis can not been named *within* Patch demo. Wikis are listed within Patch demo by the creator and the list of patches (potentially multiple) used to create it. They are also assigned a random hash, which becomes part of the URL.

### Is it possible to add extensions that are in development?

These will be considered on a case-by-case basis, but will generally be allowed as long as they don't interfere with other teams' ability to test in a production-like environment.

### What if I don't like the above restrictions?

You can run your own version of the entire Patch demo website. Get yourself a server and follow the [Setup](#setup) instructions above, or convince an engineer near you to do it.

### Is it possible to add patches for extension not just core? And skins?

Yes, patches for many extensions and skins are supported (mostly those included in MediaWiki releases, or enabled on all Wikimedia wikis), as well as Parsoid. Check out the list under "Choose extensions to enable" in the interface.

### What happens to a Patch demo wiki when the underlying patch is updated?

Nothing. Once created, the wikis are never updated. New versions of the selected patches are not applied, and neither are patches merged into master. If you want to test a newer version of the patch, create a new wiki with it.



