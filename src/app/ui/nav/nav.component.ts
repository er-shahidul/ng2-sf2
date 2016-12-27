import {Component, Input} from '@angular/core';
import {NavItem} from "../share/nav-item";

@Component({
  selector: 'rbs-nav',
  templateUrl: './nav.component.html'
})
export class NavComponent {

  @Input() links: NavItem[];
  @Input() title = 'Menu Title';

  isCollapsed=true;
}
