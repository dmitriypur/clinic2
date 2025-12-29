import {isServer} from "../target";

let scrollPosition = 0;

export class ScrollLockManager {
  scrollLocks = 0;
  locked = false;

  registerScrollLock() {
    this.scrollLocks += 1;
    this.handleScrollLocking();
  }

  unregisterScrollLock() {
    this.scrollLocks -= 1;
    this.handleScrollLocking();
  }

  handleScrollLocking() {
    if (isServer) return;

    const {scrollLocks} = this;
    const {body} = document;
    const wrapper = body.firstElementChild;

    if (scrollLocks === 0) {
      body.classList.remove('overflow-hidden');
      body.classList.remove('m-0');
      if (wrapper) {
        wrapper.classList.remove('overflow-hidden');
        wrapper.classList.remove('h-full');
      }
      window.scroll(0, scrollPosition);
      this.locked = false;
    } else if (scrollLocks > 0 && !this.locked) {
      scrollPosition = window.pageYOffset;
      body.classList.add('overflow-hidden');
      body.classList.add('m-0');

      if (wrapper) {
        wrapper.classList.add('overflow-hidden');
        wrapper.classList.add('h-full');
        wrapper.scrollTop = scrollPosition;
      }
      this.locked = true;
    }
  }
}
