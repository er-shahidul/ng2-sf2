import { Component } from '@angular/core';
import {NavItem} from "./ui/share/nav-item";

@Component({
  selector: 'app-root',
  templateUrl: './app.component.html',
  styleUrls: ['./app.component.css']
})
export class AppComponent {
  title = 'User manager';

  pages: NavItem[] = [
    {
      path: "/users", label: "Users", children: [
      {path: "/users/list", label: "User List"},
      {path: "/users/new", label: "New User"},
    ]
    },
  ];
}
