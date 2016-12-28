import {Component, OnInit, Input, ElementRef} from '@angular/core';
import {NavItem} from "../../share/nav-item";

@Component({
  selector: 'rbs-nav-item',
  templateUrl: './nav-item.component.html',
  styles: []
})
export class NavItemComponent implements OnInit {

  @Input() link:NavItem;

  constructor(private el: ElementRef) { }

  ngOnInit() {
    var nativeElement: HTMLElement = this.el.nativeElement,
      parentElement: HTMLElement = nativeElement.parentElement;
    // move all children out of the element
    while (nativeElement.firstChild) {
      parentElement.insertBefore(nativeElement.firstChild, nativeElement);
    }
    // remove the empty element
    parentElement.removeChild(nativeElement);
  }

}
