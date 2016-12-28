import {Component, OnInit} from '@angular/core';
import {NavItem} from "./ui/share/nav-item";
import {Router, ActivatedRouteSnapshot, NavigationEnd} from "@angular/router";
import {Title} from "@angular/platform-browser";

@Component({
  selector: 'app-root',
  templateUrl: './app.component.html',
  styleUrls: ['./app.component.css']
})
export class AppComponent implements OnInit{
  title = 'User manager';

  pages: NavItem[] = [
    {
      path: "/users", label: "Users", children: [
      {path: "/users/list", label: "User List"},
      {path: "/users/new", label: "New User"},
    ]
    },
  ];

  constructor(private router: Router, private titleRef:Title) {
  }

  private getDeepestTitle(routeSnapshot: ActivatedRouteSnapshot) {
    let title = routeSnapshot.data ? routeSnapshot.data['title'] : '';
    if (routeSnapshot.firstChild) {
      title = this.getDeepestTitle(routeSnapshot.firstChild) || title;
    }
    return title;
  }

  ngOnInit() {
    this.router.events.subscribe((event) => {
      if (event instanceof NavigationEnd) {
        this.title = this.getDeepestTitle(this.router.routerState.snapshot.root);
        this.titleRef.setTitle(this.title == "" ? "Home Page" :this.title);
      }
    });
  }
}
