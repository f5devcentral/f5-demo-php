colors = ['656263','ffd734','0194d2','a0bf37','ed7b0c','004892']
cmd = "sudo docker run -d -p 80 -e TZ=America/New_York -e F5DEMO_NODENAME=\"F5 Docker vLab\" -e F5DEMO_SHORT_NODENAME=server%(x)d -e F5DEMO_COLOR=%(color)s --hostname server%(x)d --name server%(x)d erchen/f5demo"
for x in range(len(colors)):
    print cmd %({'color':colors[x],'x':x+1})

